<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Placement;
use Illuminate\Support\Facades\DB;

class BuildPlacementPaths extends Command
{
    protected $signature = 'simul:build-paths';
    protected $description = 'placements 노드들의 path를 메모리 배치 처리로 고속 생성합니다.';

    public function handle()
    {
        $this->info('트리 경로(path) 일괄 생성을 시작합니다...');

        $scenarios = Placement::distinct()->pluck('scenario_id');

        foreach ($scenarios as $scenarioId) {
            $this->info("시나리오 ID {$scenarioId} 전체 데이터를 메모리로 로딩 중...");

            // 1. 해당 시나리오 전체 데이터를 메모리에 1회 로딩
            $allNodes = Placement::where('scenario_id', $scenarioId)->get();
            if ($allNodes->isEmpty()) continue;

            // 2. 트리 매핑 생성
            $childrenMap = [];
            $roots = [];

            foreach ($allNodes as $node) {
                $sponsor = $node->sponsor_id;
                if (empty($sponsor) || $sponsor === 'm') {
                    $roots[] = $node;
                } else {
                    $childrenMap[$sponsor][] = $node;
                }
            }

            $this->info("최상위 노드 " . count($roots) . "개 발견. 메모리 상에서 경로 탐색 중...");

            // 3. 메모리 내에서 path 고속 계산
            $updates = [];

            $traverse = function($node, $currentPath) use (&$traverse, &$childrenMap, &$updates) {
                $updates[$node->id] = $currentPath;

                if (isset($childrenMap[$node->node_id])) {
                    foreach ($childrenMap[$node->node_id] as $child) {
                        $traverse($child, $currentPath . '/' . $child->node_id);
                    }
                }
            };

            foreach ($roots as $root) {
                $startPath = ($root->sponsor_id === 'm') ? 'm/' . $root->node_id : (string)$root->node_id;
                $traverse($root, $startPath);
            }

            $total = count($updates);
            $this->info("경로 계산 완료! DB 초고속 일괄 반영 중 (총 {$total}건)...");

            // 4. CASE문 + 트랜잭션으로 1,000건씩 묶어서 초고속 업데이트
            DB::beginTransaction();
            try {
                $chunks = array_chunk($updates, 1000, true);
                foreach ($chunks as $chunk) {
                    $cases = [];
                    $ids = [];
                    foreach ($chunk as $id => $pathVal) {
                        $escapedPath = DB::getPdo()->quote($pathVal);
                        $cases[] = "WHEN id = {$id} THEN {$escapedPath}";
                        $ids[] = $id;
                    }
                    $sql = "UPDATE placements SET path = CASE " . implode(' ', $cases) . " END WHERE id IN (" . implode(',', $ids) . ")";
                    DB::statement($sql);
                }
                DB::commit();
                $this->info("시나리오 ID {$scenarioId} 배치 업데이트 성공!");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("에러 발생: " . $e->getMessage());
            }
        }

        $this->info('🎉 모든 노드의 path 생성이 완료되었습니다!');
    }
}
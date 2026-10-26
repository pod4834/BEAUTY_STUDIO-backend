<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SimulationController extends Controller
{
    private function ensureTables()
    {
        try {
            if (!Schema::hasTable('scenarios')) {
                Schema::create('scenarios', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->text('memo')->nullable();
                    $table->timestamps();
                });

                DB::table('scenarios')->insert([
                    ['id' => 1, 'name' => 'CROWN달성, 2개월주기 43개월차(3년7개월)', 'created_at' => now(), 'updated_at' => now()],
                    ['id' => 2, 'name' => 'CROWN달성, 3개월주기 64개월차(5년4개월)', 'created_at' => now(), 'updated_at' => now()],
                    ['id' => 3, 'name' => 'CROWN달성, 4개월주기 85개월차(7년1개월)', 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
            
            if (!Schema::hasTable('placements')) {
                Schema::create('placements', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('scenario_id')->default(1);
                    $table->string('sponsor_id');
                    $table->string('path', 500)->nullable()->index();
                    $table->string('node_id');
                    $table->string('full_code')->nullable();
                    $table->string('recommender_id')->nullable();
                    $table->string('line_type')->default('A');
                    $table->integer('period_num')->default(1);
                    $table->string('period_label')->default('1-전');
                    $table->integer('pv')->default(700000);
                    $table->integer('cv')->default(700000);
                    $table->timestamps();
                });
            }
        } catch (\Exception $e) {
            // DB Lock 예외 무시
        }
    }

    public function getScenarios()
    {
        try {
            $this->ensureTables();
            $scenarios = DB::table('scenarios')->select('id', 'name')->orderBy('id', 'asc')->get();
            if ($scenarios->isEmpty()) {
                $scenarios = [
                    ['id' => 1, 'name' => 'CROWN달성, 2개월주기 43개월차(3년7개월)'],
                    ['id' => 2, 'name' => 'CROWN달성, 3개월주기 64개월차(5년4개월)'],
                    ['id' => 3, 'name' => 'CROWN달성, 4개월주기 85개월차(7년1개월)'],
                ];
            }
            return response()->json(['status' => 'success', 'data' => $scenarios]);
        } catch (\Exception $e) { 
            return response()->json(['status' => 'success', 'data' => [
                ['id' => 1, 'name' => 'CROWN달성, 2개월주기 43개월차(3년7개월)'],
                ['id' => 2, 'name' => 'CROWN달성, 3개월주기 64개월차(5년4개월)'],
                ['id' => 3, 'name' => 'CROWN달성, 4개월주기 85개월차(7년1개월)'],
            ]]);
        }
    }

    public function createEmptyScenario(Request $request)
    {
        try {
            $this->ensureTables();
            $name = trim($request->input('name', ''));
            if (empty($name)) {
                $name = '신규 시나리오 ' . date('Y-m-d H:i');
            }

            $id = DB::table('scenarios')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('placements')->where('scenario_id', $id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => "'{$name}' 신규 시나리오가 생성되었습니다.\n나 자신(m)을 기준으로 자유롭게 회원을 추가해보세요!",
                'data' => ['id' => $id, 'name' => $name]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function seedPeriod(Request $request)
    {
        ini_set('memory_limit', '-1'); 
        set_time_limit(0); 
        DB::disableQueryLog(); 

        $this->ensureTables();
        $scenarioId = (int)$request->input('scenario_id', 1);
        $monthsMap = [1 => 2, 2 => 3, 3 => 4];
        $months = (int)$request->input('months', $monthsMap[$scenarioId] ?? 3);
        if (!in_array($months, [2, 3, 4])) $months = 3;

        DB::beginTransaction();
        try {
            DB::table('placements')->where('scenario_id', $scenarioId)->delete();

            $insertData = [];
            $aCount = 1; $bCount = 1;
            $m_accA_PV = 0; $m_accB_PV = 0;

            $buckets = [];
            $buckets[1] = [[1, 'm', 0, 'm', '', 'A', 'ROOT']]; 

            for ($currP = 1; $currP <= 400; $currP++) {
                if (!isset($buckets[$currP])) continue;

                foreach ($buckets[$currP] as $item) {
                    $p = $item[0]; $currId = $item[1]; $currDepth = $item[2]; $currPath = $item[3];
                    $sponsorId = $item[4]; $lineType = $item[5]; $lineFromM = $item[6];

                    if ($currId !== 'm') {
                        $pLabel = ceil($currP / 2) . '-' . ($currP % 2 !== 0 ? '전' : '후');
                        $insertData[] = [
                            'scenario_id' => $scenarioId,
                            'sponsor_id'  => $sponsorId,
                            'path'        => $currPath,
                            'node_id'     => $currId,
                            'full_code'   => "{$sponsorId}-{$currDepth}-{$currId}",
                            'recommender_id' => $sponsorId,
                            'line_type'   => $lineType,
                            'period_num'  => $currP,
                            'period_label' => $pLabel,
                            'pv'          => 700000,
                            'cv'          => 700000,
                            'created_at'  => now(),
                            'updated_at'  => now()
                        ];

                        if ($lineFromM === 'A') $m_accA_PV += 700000;
                        elseif ($lineFromM === 'B') $m_accB_PV += 700000;

                        if (min($m_accA_PV, $m_accB_PV) >= 10500000000) break 2;
                    }

                    $pA = $currP + ($months * 2);
                    $pB = $currP + ($months * 4);

                    $childLineA = ($currId === 'm') ? 'A' : $lineFromM;
                    $childLineB = ($currId === 'm') ? 'B' : $lineFromM;

                    $childAId = 'a' . ($aCount++);
                    $buckets[$pA][] = [$pA, $childAId, $currDepth + 1, "{$currPath}/{$childAId}", $currId, 'A', $childLineA];
                    
                    $childBId = 'b' . ($bCount++);
                    $buckets[$pB][] = [$pB, $childBId, $currDepth + 1, "{$currPath}/{$childBId}", $currId, 'B', $childLineB];
                }
                unset($buckets[$currP]);
            }

            if (!empty($insertData)) {
                foreach (array_chunk($insertData, 2000) as $chunk) {
                    DB::table('placements')->insert($chunk);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success', 
                'message' => "시나리오 {$scenarioId} ({$months}개월 주기) DB 생성이 완료되었습니다.\n(총 " . number_format(count($insertData)) . "명 배정)"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function seedAllPresets()
    {
        try {
            $presets = [1 => 2, 2 => 3, 3 => 4];
            foreach ($presets as $sId => $m) {
                $req = new Request(['scenario_id' => $sId, 'months' => $m]);
                $this->seedPeriod($req);
            }
            return response()->json(['status' => 'success', 'message' => "2개월, 3개월, 4개월 전체 시나리오 DB 구축이 완료되었습니다!"]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function savePlacements(Request $request)
    {
        $this->ensureTables();
        DB::disableQueryLog();
        $scenarioId = (int)$request->input('scenario_id', 1);

        if (in_array($scenarioId, [1, 2, 3])) {
            return response()->json(['status' => 'error', 'message' => '기본 제공 시나리오(2/3/4개월)는 원본이 보호됩니다. 새 시나리오로 복사하여 저장하세요.'], 400);
        }

        $placements = $request->input('placements', []);

        DB::beginTransaction();
        try {
            DB::table('placements')->where('scenario_id', $scenarioId)->delete();
            $pathMap = [];
            $insertData = [];

            foreach ($placements as $p) {
                $sponsor = $p['sponsor_id'];
                $nodeId = $p['node_id'];
                if (isset($p['path']) && !empty($p['path'])) $path = $p['path'];
                else if (empty($sponsor) || $sponsor === 'm') $path = ($sponsor === 'm') ? "m/{$nodeId}" : (string)$nodeId;
                else { $parentPath = $pathMap[$sponsor] ?? "m/{$sponsor}"; $path = "{$parentPath}/{$nodeId}"; }
                $pathMap[$nodeId] = $path;

                $insertData[] = [
                    'scenario_id'   => $scenarioId,
                    'sponsor_id'    => $sponsor,
                    'path'          => $path,
                    'node_id'       => $nodeId,
                    'full_code'     => $p['full_code'] ?? null,
                    'recommender_id' => $p['recommender_id'] ?? $sponsor,
                    'line_type'     => $p['line_type'] ?? 'A',
                    'period_num'    => $p['period_num'] ?? 1,
                    'period_label'  => $p['period_label'] ?? '1-전',
                    'pv'            => $p['pv'] ?? 700000,
                    'cv'            => $p['cv'] ?? 700000,
                ];
            }
            if (!empty($insertData)) {
                foreach (array_chunk($insertData, 1000) as $chunk) {
                    DB::table('placements')->insert($chunk);
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'DB 저장 완료']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getPlacements(Request $request)
    {
        $this->ensureTables();
        $scenarioId = $request->query('scenario_id', 1);

        $data = DB::table('placements')
            ->select('id', 'scenario_id', 'sponsor_id', 'node_id', 'full_code', 'recommender_id', 'line_type', 'period_num', 'pv', 'cv')
            ->where('scenario_id', $scenarioId)
            ->orderBy('period_num', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function calculateSimulation(Request $request)
    {
        ini_set('memory_limit', '-1'); 
        set_time_limit(0); 
        DB::disableQueryLog(); 

        $this->ensureTables();
        $scenarioId = (int)$request->query('scenario_id', 2);

        $placements = DB::table('placements')
            ->select('id', 'sponsor_id', 'node_id', 'full_code', 'recommender_id', 'line_type', 'period_num', 'pv', 'cv')
            ->where('scenario_id', $scenarioId)
            ->orderBy('period_num', 'asc')
            ->get();

        $rankLevels = [
            'GUEST' => 0, 'MANIA' => 1, 'DIRECTOR' => 2, 'EMERALD' => 3,
            'DIAMOND' => 4, 'GREEN DIAMOND' => 5, 'BLUE DIAMOND' => 6, 'RED DIAMOND' => 7, 'CROWN' => 8
        ];

        $THRESHOLDS = [
            'DIRECTOR' => 2100000, 'EMERALD' => 14000000, 'DIAMOND' => 100000000, 
            'GREEN_DIAMOND' => 300000000, 'BLUE_DIAMOND' => 1000000000, 'RED_DIAMOND' => 3000000000, 'CROWN' => 10500000000
        ];

        $getSubAmount = function($rank) {
            if (in_array($rank, ['DIRECTOR', 'EMERALD'])) return 10000;
            if (in_array($rank, ['DIAMOND', 'GREEN DIAMOND'])) return 30000;
            if (in_array($rank, ['BLUE DIAMOND', 'RED DIAMOND', 'CROWN'])) return 70000;
            return 0;
        };

        $parentMap = [];
        $signupPeriodMap = ['m' => 1];
        $fullCodeMap = ['m' => 'm'];
        $allMemberIds = ['m'];
        $maxPeriodInDb = 1;

        $dbGroupCounts = [];
        $placementsByPeriod = [];

        foreach ($placements as $p) {
            $node = strtolower($p->node_id);
            $sponsor = strtolower($p->sponsor_id);
            $rec = strtolower($p->recommender_id ?: ($p->full_code ? explode('-', $p->full_code)[0] : $sponsor));

            $parentMap[$node] = [
                'parent' => $sponsor,
                'line' => strtoupper($p->line_type),
                'recommender' => $rec,
                'cv' => (int)($p->cv ?? 700000),
                'pv' => (int)($p->pv ?? 700000),
            ];
            $signupPeriodMap[$node] = (int)$p->period_num;
            $fullCodeMap[$node] = $p->full_code ?: $node;
            $allMemberIds[] = $node;
            $placementsByPeriod[(int)$p->period_num][] = $p;

            $pNum = (int)$p->period_num;
            $lineGrp = (strpos($node, 'a') === 0) ? 'A' : 'B';
            if (!isset($dbGroupCounts[$pNum][$lineGrp])) {
                $dbGroupCounts[$pNum][$lineGrp] = ['pv' => 0, 'cv' => 0, 'cnt' => 0];
            }
            $dbGroupCounts[$pNum][$lineGrp]['pv'] += (int)($p->pv ?? 700000);
            $dbGroupCounts[$pNum][$lineGrp]['cv'] += (int)($p->cv ?? 700000);
            $dbGroupCounts[$pNum][$lineGrp]['cnt'] += 1;

            if ($pNum > $maxPeriodInDb) {
                $maxPeriodInDb = $pNum;
            }
        }

        $rootLineMap = [];
        foreach ($allMemberIds as $id) {
            if ($id === 'm') { $rootLineMap[$id] = 'M'; continue; }
            $curr = $id; $rootLine = 'A';
            $safeGuard = 0; 
            while ($curr !== null && isset($parentMap[$curr]) && $safeGuard < 1000) {
                if ($parentMap[$curr]['parent'] === 'm') {
                    $rootLine = strtoupper($parentMap[$curr]['line']);
                    break;
                }
                $curr = $parentMap[$curr]['parent'];
                $safeGuard++;
            }
            $rootLineMap[$id] = $rootLine;
        }

        $monthsMap = [1 => 2, 2 => 3, 3 => 4];
        $months = $monthsMap[$scenarioId] ?? 3;
        $crownMonth = ($months === 2) ? 43 : (($months === 3) ? 64 : 85);
        $maxPeriodNum = max($maxPeriodInDb, $crownMonth * 2);

        $RootRankAtAge = [];
        $accA_PV_temp = 0; $accB_PV_temp = 0; $highestRankLevel_temp = 1; $currentRank_temp = 'MANIA'; $promotedAge_temp = 0;

        for ($age = 1; $age <= $maxPeriodNum; $age++) {
            $newA_PV_t = $dbGroupCounts[$age]['A']['pv'] ?? 0;
            $newB_PV_t = $dbGroupCounts[$age]['B']['pv'] ?? 0;
            $accA_PV_temp += $newA_PV_t;
            $accB_PV_temp += $newB_PV_t;

            $smallLegPV = min($accA_PV_temp, $accB_PV_temp);
            $nextRank = 'MANIA';
            if ($smallLegPV >= $THRESHOLDS['CROWN']) $nextRank = 'CROWN';
            elseif ($smallLegPV >= $THRESHOLDS['RED_DIAMOND']) $nextRank = 'RED DIAMOND';
            elseif ($smallLegPV >= $THRESHOLDS['BLUE_DIAMOND']) $nextRank = 'BLUE DIAMOND';
            elseif ($smallLegPV >= $THRESHOLDS['GREEN_DIAMOND']) $nextRank = 'GREEN DIAMOND';
            elseif ($smallLegPV >= $THRESHOLDS['DIAMOND']) $nextRank = 'DIAMOND';
            elseif ($smallLegPV >= $THRESHOLDS['EMERALD']) $nextRank = 'EMERALD';
            elseif ($smallLegPV >= $THRESHOLDS['DIRECTOR']) $nextRank = 'DIRECTOR';

            $isNewRank = false;
            $nextRankLevel = $rankLevels[$nextRank] ?? 1;
            if ($nextRankLevel > $highestRankLevel_temp) {
                $highestRankLevel_temp = $nextRankLevel;
                $currentRank_temp = $nextRank;
                $promotedAge_temp = $age;
                $isNewRank = true;
            }

            $RootRankAtAge[$age] = [
                'rank' => $currentRank_temp,
                'level' => $highestRankLevel_temp,
                'isNewRank' => $isNewRank,
                'promotedAge' => $promotedAge_temp
            ];
        }

        // 'm'의 1~5대 추천 계보 파트너만 추출
        $recGenMap = [];
        foreach ($allMemberIds as $id) {
            if ($id === 'm') continue;
            $rec = $parentMap[$id]['recommender'] ?? 'm';
            if ($rec === 'm') $recGenMap[$id] = 1;
        }
        for ($g = 2; $g <= 5; $g++) {
            foreach ($allMemberIds as $id) {
                if (isset($recGenMap[$id])) continue;
                $rec = $parentMap[$id]['recommender'] ?? 'm';
                if (isset($recGenMap[$rec]) && $recGenMap[$rec] === ($g - 1)) {
                    $recGenMap[$id] = $g;
                }
            }
        }

        $targetMatchingMembers = array_keys($recGenMap);
        $fastLookupTarget = array_flip($targetMatchingMembers);

        $downlineCarryA = []; $downlineCarryB = [];
        foreach ($targetMatchingMembers as $X) { $downlineCarryA[$X] = 0; $downlineCarryB[$X] = 0; }
        $downlineTeamBonusByPeriod = [];

        // 초고속 Bubble-Up
        for ($p_calc = 1; $p_calc <= $maxPeriodNum; $p_calc++) {
            $pNewCV_A = []; $pNewCV_B = [];
            $cvSources = [];

            if (isset($placementsByPeriod[$p_calc])) {
                foreach ($placementsByPeriod[$p_calc] as $node) {
                    $cvSources[strtolower($node->node_id)] = (int)($node->cv ?? 700000);
                }
            }

            foreach ($placementsByPeriod as $joinP => $nodes) {
                if ($joinP >= $p_calc) continue;
                $age = $p_calc - $joinP + 1;
                $subInfo = $RootRankAtAge[$age] ?? null;
                if ($subInfo && $age > $subInfo['promotedAge']) {
                    $subAmt = $getSubAmount($subInfo['rank']);
                    if ($subAmt > 0) {
                        foreach ($nodes as $node) {
                            $y = strtolower($node->node_id);
                            if ($y !== 'm') {
                                $cvSources[$y] = ($cvSources[$y] ?? 0) + $subAmt;
                            }
                        }
                    }
                }
            }

            foreach ($cvSources as $sourceNode => $cvAmt) {
                $curr = $sourceNode;
                $safeGuard = 0;
                while (isset($parentMap[$curr]) && $safeGuard < 1000) {
                    $parent = $parentMap[$curr]['parent'];
                    $line = strtoupper($parentMap[$curr]['line']);
                    
                    if (isset($fastLookupTarget[$parent])) {
                        if ($line === 'A') $pNewCV_A[$parent] = ($pNewCV_A[$parent] ?? 0) + $cvAmt;
                        else $pNewCV_B[$parent] = ($pNewCV_B[$parent] ?? 0) + $cvAmt;
                    }
                    
                    $curr = $parent;
                    if ($curr === 'm' || empty($curr)) break;
                    $safeGuard++;
                }
            }

            foreach ($targetMatchingMembers as $X) {
                $availA = $downlineCarryA[$X] + ($pNewCV_A[$X] ?? 0);
                $availB = $downlineCarryB[$X] + ($pNewCV_B[$X] ?? 0);
                $matched = min($availA, $availB);
                $downlineCarryA[$X] = $availA - $matched;
                $downlineCarryB[$X] = $availB - $matched;

                $tb = ($p_calc === 1) ? 0 : min((int)floor($matched * 0.12), 50000000);
                $downlineTeamBonusByPeriod[$X][$p_calc] = $tb;
            }
        }

        $recommenderChildrenMap = [];
        foreach ($targetMatchingMembers as $id) {
            $rec = $parentMap[$id]['recommender'] ?? 'm';
            $recommenderChildrenMap[$rec][] = $id;
        }

        // 압축(Roll-up) 처리 재귀 함수
        $getCompressedMatching = function($currentId, $currentGen, $maxAllowedGen, $genRates, $p, &$recommenderChildrenMap, &$downlineTeamBonusByPeriod, &$fullCodeMap, &$matchingDetails, &$totalMatching) use (&$getCompressedMatching) {
            if ($currentGen > $maxAllowedGen) return;
            if (!isset($recommenderChildrenMap[$currentId])) return;

            foreach ($recommenderChildrenMap[$currentId] as $childId) {
                $tb = $downlineTeamBonusByPeriod[$childId][$p] ?? 0;
                
                if ($tb >= 30000) {
                    if (isset($genRates[$currentGen])) {
                        $rate = $genRates[$currentGen];
                        $mAmt = (int)floor($tb * $rate);
                        $matchingDetails[] = [
                            'gen' => $currentGen,
                            'fullCode' => $fullCodeMap[$childId] ?? strtoupper($childId),
                            'teamBonus' => $tb,
                            'rate' => $rate,
                            'matchingAmount' => $mAmt
                        ];
                        $totalMatching += $mAmt;
                    }
                    $getCompressedMatching($childId, $currentGen + 1, $maxAllowedGen, $genRates, $p, $recommenderChildrenMap, $downlineTeamBonusByPeriod, $fullCodeMap, $matchingDetails, $totalMatching);
                } else {
                    $getCompressedMatching($childId, $currentGen, $maxAllowedGen, $genRates, $p, $recommenderChildrenMap, $downlineTeamBonusByPeriod, $fullCodeMap, $matchingDetails, $totalMatching);
                }
            }
        };

        $accA_PV = 0; $accB_PV = 0;
        $accA_CV = 0; $accB_CV = 0;
        $carryA = 0; $carryB = 0;

        $generatedLevelBonusMap = [];
        $generatedCarBonusMap = [];

        $prevDirectBonus = 0;
        $prevTeamBonus = 0;
        $prevMatchingBonus = 0;
        $prevMatchingDetails = [];

        $periodResults = [];

        for ($p = 1; $p <= $maxPeriodNum; $p++) {
            $monthNum = ceil($p / 2);
            $isFirstHalf = ($p % 2 !== 0);

            $signupA_PV = $dbGroupCounts[$p]['A']['pv'] ?? 0;
            $signupA_CV = $dbGroupCounts[$p]['A']['cv'] ?? 0;
            $signupB_PV = $dbGroupCounts[$p]['B']['pv'] ?? 0;
            $signupB_CV = $dbGroupCounts[$p]['B']['cv'] ?? 0;

            $generatedDirectBonus = 0;
            if (isset($placementsByPeriod[$p])) {
                foreach ($placementsByPeriod[$p] as $node) {
                    $rec = strtolower($node->recommender_id ?: ($node->full_code ? explode('-', $node->full_code)[0] : $node->sponsor_id));
                    if (strtolower($node->node_id) !== 'm' && $rec === 'm') {
                        $generatedDirectBonus += (int)floor(($node->pv ?? 700000) * 0.12);
                    }
                }
            }

            $subA_CV = 0; $subB_CV = 0;
            $periodMaintenanceList = [];

            foreach ($placementsByPeriod as $joinP => $nodes) {
                if ($joinP >= $p) continue;
                $age = $p - $joinP + 1;
                $subInfo = $RootRankAtAge[$age] ?? null;
                if (!$subInfo) continue;

                if ($age > 1 && $age > $subInfo['promotedAge']) {
                    $subAmt = $getSubAmount($subInfo['rank']);
                    if ($subAmt > 0) {
                        foreach ($nodes as $node) {
                            if (strtolower($node->node_id) === 'm') continue;
                            
                            $rLine = $rootLineMap[strtolower($node->node_id)] ?? 'A';
                            if ($rLine === 'A') $subA_CV += $subAmt;
                            else $subB_CV += $subAmt;

                            if (count($periodMaintenanceList) < 30) {
                                $periodMaintenanceList[] = [
                                    'node_id' => $node->node_id,
                                    'full_code' => $node->full_code ?: $node->node_id,
                                    'rank' => $subInfo['rank'],
                                    'cv' => $subAmt,
                                    'pv' => $subAmt,
                                    'line_type' => $node->line_type ?: 'A',
                                    'recommender_id' => $node->recommender_id ?: 'm',
                                    'sponsor_id' => $node->sponsor_id ?: 'm',
                                    'type' => '구독'
                                ];
                            }
                        }
                    }
                }
            }

            $rootInfo = $RootRankAtAge[$p];
            $currentRank = $rootInfo['rank'];
            $isNewRank = $rootInfo['isNewRank'];

            if (!$isNewRank && $p > $rootInfo['promotedAge']) {
                $mSubAmt = $getSubAmount($currentRank);
                if ($mSubAmt > 0) {
                    array_unshift($periodMaintenanceList, [
                        'node_id' => 'm',
                        'full_code' => 'm',
                        'rank' => $currentRank,
                        'cv' => $mSubAmt,
                        'pv' => $mSubAmt,
                        'line_type' => '-',
                        'recommender_id' => '-',
                        'sponsor_id' => '-',
                        'type' => '구독'
                    ]);
                }
            }

            $totalA_PV = $signupA_PV + $subA_CV;
            $totalB_PV = $signupB_PV + $subB_CV;
            $totalA_CV = $signupA_CV + $subA_CV;
            $totalB_CV = $signupB_CV + $subB_CV;

            $accA_PV += $totalA_PV;
            $accB_PV += $totalB_PV;
            $accA_CV += $totalA_CV;
            $accB_CV += $totalB_CV;

            $availA = $carryA + $totalA_CV;
            $availB = $carryB + $totalB_CV;
            $matchedCV = min($availA, $availB);

            $carryA = $availA - $matchedCV;
            $carryB = $availB - $matchedCV;

            $teamRate = in_array($currentRank, ['RED DIAMOND', 'CROWN']) ? 0.10 : 0.12;
            $generatedTeamBonus = ($p === 1) ? 0 : min((int)floor($matchedCV * $teamRate), 50000000);

            $genRates = [];
            if ($currentRank === 'DIRECTOR') $genRates = [1 => 0.10];
            elseif ($currentRank === 'EMERALD') $genRates = [1 => 0.10, 2 => 0.05];
            elseif ($currentRank === 'DIAMOND') $genRates = [1 => 0.10, 2 => 0.05, 3 => 0.05];
            elseif ($currentRank === 'GREEN DIAMOND') $genRates = [1 => 0.10, 2 => 0.05, 3 => 0.05, 4 => 0.02];
            elseif ($currentRank === 'BLUE DIAMOND') $genRates = [1 => 0.10, 2 => 0.05, 3 => 0.05, 4 => 0.03];
            elseif ($currentRank === 'RED DIAMOND') $genRates = [1 => 0.10, 2 => 0.05, 3 => 0.05, 4 => 0.03, 5 => 0.02];
            elseif ($currentRank === 'CROWN') $genRates = [1 => 0.10, 2 => 0.05, 3 => 0.05, 4 => 0.03, 5 => 0.03];

            $generatedMatchingBonus = 0;
            $generatedMatchingDetails = [];
            if (!empty($genRates)) {
                $maxAllowedGen = max(array_keys($genRates));
                $getCompressedMatching('m', 1, $maxAllowedGen, $genRates, $p, $recommenderChildrenMap, $downlineTeamBonusByPeriod, $fullCodeMap, $generatedMatchingDetails, $generatedMatchingBonus);
            }

            $rankCapMap = [
                'DIRECTOR' => 150000, 'EMERALD' => 300000, 'DIAMOND' => 750000,
                'GREEN DIAMOND' => 1400000, 'BLUE DIAMOND' => 2300000, 'RED DIAMOND' => 4000000, 'CROWN' => 6000000
            ];
            $maxRankCap = $rankCapMap[$currentRank] ?? 0;

            $lesserLegNewCV = min($totalA_CV, $totalB_CV);
            $rawLevelBonus = 0; $rawCarBonus = 0;
            if ($rootInfo['level'] >= 2) {
                if ($lesserLegNewCV >= 135000000) { $rawLevelBonus = 6000000; $rawCarBonus = 4000000; }
                elseif ($lesserLegNewCV >= 65000000) { $rawLevelBonus = 4000000; $rawCarBonus = 2000000; }
                elseif ($lesserLegNewCV >= 34000000) { $rawLevelBonus = 2300000; $rawCarBonus = 1500000; }
                elseif ($lesserLegNewCV >= 19000000) { $rawLevelBonus = 1400000; $rawCarBonus = 1000000; }
                elseif ($lesserLegNewCV >= 10000000) { $rawLevelBonus = 750000; $rawCarBonus = 800000; }
                elseif ($lesserLegNewCV >= 3500000) { $rawLevelBonus = 300000; $rawCarBonus = 0; }
                elseif ($lesserLegNewCV >= 2100000) { $rawLevelBonus = 150000; $rawCarBonus = 0; }
            }

            $generatedLevelBonusMap[$p] = min($rawLevelBonus, $maxRankCap);
            $generatedCarBonusMap[$p] = $rawCarBonus;

            // 💡 [핵심] 다음 기수($P+1$) 실지급 매핑 시 검증 명세 배열까지 동기화 저장
            $directBonusToPay = $prevDirectBonus;
            $teamBonusToPay = $prevTeamBonus;
            $matchingBonusToPay = $prevMatchingBonus;
            $matchingDetailsToPay = $prevMatchingDetails;

            $prevDirectBonus = $generatedDirectBonus;
            $prevTeamBonus = $generatedTeamBonus;
            $prevMatchingBonus = $generatedMatchingBonus;
            $prevMatchingDetails = $generatedMatchingDetails;

            $levelBonusToPay = $generatedLevelBonusMap[$p - 1] ?? 0;

            $carBonusToPay = 0;
            if ($isFirstHalf) {
                $prevCar1 = $generatedCarBonusMap[$p - 2] ?? 0; 
                $prevCar2 = $generatedCarBonusMap[$p - 1] ?? 0; 
                $carBonusToPay = max($prevCar1, $prevCar2);
            }

            $firstPower = 0;
            $firstPowerReason = '';
            
            if ($p > 2 && $isFirstHalf) {
                $prevMonthStart = $p - 2; 
                $prevMonthEnd = $p - 1;   

                $step1_achieved = false;
                $step1_users = [];
                if (isset($placementsByPeriod[$prevMonthStart])) {
                    foreach ($placementsByPeriod[$prevMonthStart] as $node) {
                        $rec = strtolower($node->recommender_id ?: ($node->full_code ? explode('-', $node->full_code)[0] : $node->sponsor_id));
                        $line = strtoupper($node->line_type);
                        if ($rec === 'm' && $line === 'A' && (int)($node->pv ?? 700000) >= 700000) {
                            $step1_users[] = strtolower($node->node_id);
                        }
                    }
                }
                if (count($step1_users) >= 2) $step1_achieved = true;

                $step2_achieved = false;
                if ($step1_achieved) {
                    $step2_qualified_sponsors = 0;
                    foreach ($step1_users as $s1_user) {
                        $sub_recruits = 0;
                        foreach ([$prevMonthStart, $prevMonthEnd] as $chk_p) {
                            if (isset($placementsByPeriod[$chk_p])) {
                                foreach ($placementsByPeriod[$chk_p] as $node) {
                                    $rec = strtolower($node->recommender_id ?: ($node->full_code ? explode('-', $node->full_code)[0] : $node->sponsor_id));
                                    if ($rec === $s1_user && (int)($node->pv ?? 700000) >= 700000) $sub_recruits++;
                                }
                            }
                        }
                        if ($sub_recruits >= 2) $step2_qualified_sponsors++;
                    }
                    if ($step2_qualified_sponsors >= 2) $step2_achieved = true;
                }

                $step3_achieved = false;
                $step3_users = [];
                foreach ([$prevMonthStart, $prevMonthEnd] as $chk_p) {
                    if (isset($placementsByPeriod[$chk_p])) {
                        foreach ($placementsByPeriod[$chk_p] as $node) {
                            $rec = strtolower($node->recommender_id ?: ($node->full_code ? explode('-', $node->full_code)[0] : $node->sponsor_id));
                            $line = strtoupper($node->line_type);
                            if ($rec === 'm' && $line === 'B' && (int)($node->pv ?? 700000) >= 700000) {
                                $step3_users[] = strtolower($node->node_id);
                            }
                        }
                    }
                }

                if (count($step3_users) >= 2) {
                    $step3_qualified_sponsors = 0;
                    foreach ($step3_users as $s3_user) {
                        $sub_recruits = 0;
                        foreach ([$prevMonthStart, $prevMonthEnd] as $chk_p) {
                            if (isset($placementsByPeriod[$chk_p])) {
                                foreach ($placementsByPeriod[$chk_p] as $node) {
                                    $rec = strtolower($node->recommender_id ?: ($node->full_code ? explode('-', $node->full_code)[0] : $node->sponsor_id));
                                    if ($rec === $s3_user && (int)($node->pv ?? 700000) >= 700000) $sub_recruits++;
                                }
                            }
                        }
                        if ($sub_recruits >= 2) $step3_qualified_sponsors++;
                    }
                    if ($step3_qualified_sponsors >= 2) $step3_achieved = true;
                }

                if ($step1_achieved && $step2_achieved && $step3_achieved) {
                    $firstPower = 600000; 
                    $firstPowerReason = 'Step 1,2,3 1사이클 달성 (100% 전액지급)';
                } elseif ($step1_achieved) {
                    $scheduled_payout = 100000 + ($step2_achieved ? 200000 : 0);
                    $firstPower = $scheduled_payout * 0.5; 
                    $reasons = ['Step 1'];
                    if ($step2_achieved) $reasons[] = 'Step 2';
                    $firstPowerReason = implode(', ', $reasons) . ' 달성 (일부 달성으로 50% 분할지급)';
                } else {
                    $firstPower = 0;
                    $firstPowerReason = '달성 실패';
                }
            } else {
                $firstPowerReason = '-';
            }

            $subCost = 0;
            if (!$isNewRank && $p > $rootInfo['promotedAge']) {
                if (in_array($currentRank, ['CROWN', 'RED DIAMOND', 'BLUE DIAMOND'])) $subCost = 269230;
                elseif (in_array($currentRank, ['GREEN DIAMOND', 'DIAMOND'])) $subCost = 115384;
                elseif (in_array($currentRank, ['EMERALD', 'DIRECTOR'])) $subCost = 38461;
            }

            $monthlyTotal = $directBonusToPay + $teamBonusToPay + $matchingBonusToPay + $levelBonusToPay + $carBonusToPay + $firstPower - $subCost;

            $rankCountsA = ['MANIA'=>0, 'DIRECTOR'=>0, 'EMERALD'=>0, 'DIAMOND'=>0, 'GREEN DIAMOND'=>0, 'BLUE DIAMOND'=>0, 'RED DIAMOND'=>0, 'CROWN'=>0];
            $rankCountsB = ['MANIA'=>0, 'DIRECTOR'=>0, 'EMERALD'=>0, 'DIAMOND'=>0, 'GREEN DIAMOND'=>0, 'BLUE DIAMOND'=>0, 'RED DIAMOND'=>0, 'CROWN'=>0];

            foreach ($dbGroupCounts as $joinP => $lines) {
                if ($p >= $joinP) {
                    $age = $p - $joinP + 1;
                    $subRank = $RootRankAtAge[$age]['rank'] ?? 'CROWN';
                    $rankCountsA[$subRank] += ($lines['A']['cnt'] ?? 0);
                    $rankCountsB[$subRank] += ($lines['B']['cnt'] ?? 0);
                }
            }

            $periodResults[] = [
                'month' => $p,
                'period_num' => $p,
                'periodLabel' => $monthNum . '-' . ($isFirstHalf ? '전' : '후'),
                'newA_CV' => $totalA_CV,
                'accA_CV' => $accA_CV,
                'availA_CV' => $availA,
                'newA_PV' => $totalA_PV,
                'accA_PV' => $accA_PV,
                'newB_CV' => $totalB_CV,
                'accB_CV' => $accB_CV,
                'availB_CV' => $availB,
                'newB_PV' => $totalB_PV,
                'accB_PV' => $accB_PV,
                'matchedCV' => $matchedCV,
                'subA_CV' => $subA_CV,
                'subB_CV' => $subB_CV,
                'rank' => $currentRank,
                'isNewRank' => $isNewRank,
                
                'directBonus' => $directBonusToPay,
                'teamBonus' => $teamBonusToPay,
                'matchingBonus' => $matchingBonusToPay,
                'matchingDetails' => $matchingDetailsToPay, // 💡 프론트엔드 모달 직접 연동용
                'levelBonus' => $levelBonusToPay,
                'carBonus' => $carBonusToPay,
                
                'generatedTeamBonus' => $generatedTeamBonus, 
                'firstPowerBonus' => $firstPower,
                'firstPowerReason' => $firstPowerReason, 
                
                'maintenanceCost' => $subCost,
                'monthlyTotal' => $monthlyTotal,
                'maintenanceList' => $periodMaintenanceList,
                'rankCountsA' => $rankCountsA,
                'rankCountsB' => $rankCountsB,
                'rankReason' => $isNewRank ? "신규 승급 ({$currentRank})" : "{$currentRank} 타이틀 유지"
            ];
        }

        return response()->json(['status' => 'success', 'data' => $periodResults]);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index() 
    { 
        return Product::all(); 
    }

    public function store(Request $request)
    {
        $imageUrl = '';
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        $product = new Product();
        $product->category          = $request->input('category');
        $product->product_category  = $request->input('product_category');
        $product->product_id        = $request->input('product_id', $request->input('code'));
        $product->product_name      = $request->input('product_name');
        $product->description_title = $request->input('description_title');
        $product->product_detail    = $request->input('product_detail');
        $product->detail_url        = $request->input('detail_url');
        $product->price             = $request->input('price', 0);
        $product->cv                = $request->input('cv', 0);
        $product->pv                = $request->input('pv', 0);
        $product->img_url           = $imageUrl; 
        $product->save();

        return response()->json([
            'message' => '상품이 성공적으로 등록되었습니다!', 
            'product' => $product
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 🟢 일반 필드 업데이트
        $product->category          = $request->input('category', $product->category);
        $product->product_category  = $request->input('product_category', $product->product_category);
        $product->product_id        = $request->input('product_id', $request->input('code', $product->product_id));
        $product->product_name      = $request->input('product_name', $product->product_name);
        
        // 🟢 description_title 및 product_detail (exists 검사로 빈 값/null도 유연하게 할당)
        if ($request->exists('description_title')) {
            $product->description_title = $request->input('description_title');
        }
        if ($request->exists('product_detail')) {
            $product->product_detail = $request->input('product_detail');
        }
        if ($request->exists('detail_url')) {
            $product->detail_url = $request->input('detail_url');
        }

        // 수치형 필드 업데이트
        if ($request->has('price')) $product->price = $request->input('price');
        if ($request->has('cv'))    $product->cv    = $request->input('cv');
        if ($request->has('pv'))    $product->pv    = $request->input('pv');

        // 이미지 파일 전송 시 처리
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->img_url = asset('storage/' . $path);
        }

        $product->save();

        // 🟢 DB에서 새로 갱신된 최신 객체 반환
        return response()->json([
            'message' => '상품 정보가 수정되었습니다!', 
            'product' => $product->fresh()
        ]);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => '상품이 삭제되었습니다.']);
    }

    public function bulkStore(Request $request)
    {
        if (!$request->hasFile('csv_file')) {
            return response()->json(['message' => '파일이 없습니다.'], 400);
        }

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), "r");

        fgetcsv($handle); // 1번째 줄(헤더) 건너뛰기

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) >= 8) {
                $product = new Product();
                $product->category          = $data[0] ?? '';
                $product->product_category  = $data[1] ?? '';
                $product->product_id        = $data[2] ?? '';
                $product->product_name      = $data[3] ?? '';
                $product->description_title = $data[4] ?? '';
                $product->product_detail    = $data[5] ?? '';
                $product->price             = (int)($data[6] ?? 0);
                $product->cv                = (int)($data[7] ?? 0);
                $product->pv                = (int)($data[8] ?? 0);
                $product->img_url           = isset($data[9]) && $data[9] !== '' ? $data[9] : 'https://via.placeholder.com/400x400.png?text=No+Image';
                $product->save();
                $count++;
            }
        }
        fclose($handle);

        return response()->json(['message' => $count . '개의 상품이 일괄 등록되었습니다!']);
    }
}
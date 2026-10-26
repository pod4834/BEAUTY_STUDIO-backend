<?php
namespace App\Http\Controllers;

use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index()
    {
        return response()->json(Gallery::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'product_ids' => 'nullable|array',
            'before_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB 이하 파일
            'after_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // 파일 저장 및 경로 생성
        if ($request->hasFile('before_image')) {
            $validated['before_image'] = '/storage/' . $request->file('before_image')->store('galleries', 'public');
        }
        if ($request->hasFile('after_image')) {
            $validated['after_image'] = '/storage/' . $request->file('after_image')->store('galleries', 'public');
        }

        $gallery = Gallery::create($validated);
        return response()->json($gallery, 201);
    }

    public function update(Request $request, $id)
    {
        $gallery = Gallery::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'product_ids' => 'nullable|array',
            'before_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'after_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // 새 파일이 업로드된 경우 기존 파일 삭제 후 새 파일 저장
        if ($request->hasFile('before_image')) {
            if ($gallery->before_image) Storage::disk('public')->delete(str_replace('/storage/', '', $gallery->before_image));
            $validated['before_image'] = '/storage/' . $request->file('before_image')->store('galleries', 'public');
        } else {
            unset($validated['before_image']); // 파일이 없으면 기존 DB 유지
        }

        if ($request->hasFile('after_image')) {
            if ($gallery->after_image) Storage::disk('public')->delete(str_replace('/storage/', '', $gallery->after_image));
            $validated['after_image'] = '/storage/' . $request->file('after_image')->store('galleries', 'public');
        } else {
            unset($validated['after_image']);
        }

        $gallery->update($validated);
        return response()->json($gallery);
    }

    public function destroy($id)
    {
        $gallery = Gallery::findOrFail($id);
        // 삭제 시 폴더에 남은 이미지 파일도 함께 깔끔하게 삭제
        if ($gallery->before_image) Storage::disk('public')->delete(str_replace('/storage/', '', $gallery->before_image));
        if ($gallery->after_image) Storage::disk('public')->delete(str_replace('/storage/', '', $gallery->after_image));
        
        $gallery->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
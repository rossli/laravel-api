<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BookController extends BaseController
{
    //
    public function index()
    {
        $books = Book::where('enabled', 1)->limit(4)->orderBy('updated_at', 'DESC')->get();
        $data = [];
        $books->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'id' => $item->id,
                'price' => $item->price,
            ];
        });
        return $this->success($data);
    }

    public function show($id)
    {
        $book = Book::find($id);
        $data = [];

        $data[] = [
            'image' => config('jkw.cdn_domain') . '/' . $book->cover,
            'title' => $book->title,
            'id' => $book->id,
            'price' => $book->price,
            'subtitle' => $book->subtitle,
            'origin_price' => $book->origin_price,
            'summary' => $book->summary,
            'menu' => $book->menu,
            'num' => $book->num,
            'student_num' => $book->student_num,
        ];
        return $this->success($data);
    }

    public function list()
    {
        $books = Book::where('enabled', 1)->orderBy('updated_at', 'DESC')->limit(6)->get();
        $data = [];
        $books->each(function ($item) use (&$data) {
            $data[] = [
                'image' => config('jkw.cdn_domain') . '/' . $item->cover,
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'id' => $item->id,
                'price' => $item->price,
            ];
        });
        return $this->success($data);
    }
}

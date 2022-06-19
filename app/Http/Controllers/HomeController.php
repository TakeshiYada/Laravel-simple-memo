<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\MemoTag;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    //ホーム画面の表示時の動き
    public function index()
    {
        return view('create');
    }

    //メモ作成時の動き
    public function store(Request $request)
    {
        $posts = $request->all();
        $request->validate([ 'content' => 'required' ]);

        //トランザクション開始
        DB::transaction(function() use($posts) {

            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]);
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])
            ->exists();

            if( (!empty($posts['new_tag']) || $posts['new_tag'] === "0") && !$tag_exists ){

                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }

            if(!empty($posts['tags'][0])){
                foreach($posts['tags'] as $tag){
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id'=> $tag]);
                }
            }
        });
        //トランザクション終了

        return redirect( route ('home'));
    }

    //メモ編集画面表示時の動き
    public function edit($id)
    {
        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
            ->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id')
            ->leftJoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
            ->where('memos.user_id', '=', \Auth::id())
            ->where('memos.id', '=', $id)
            ->whereNull('memos.deleted_at')
            ->get();

        $include_tags = [];
        foreach($edit_memo as $memo){
            array_push($include_tags, $memo['tag_id']);
        }

        return view('edit', compact('edit_memo','include_tags'));
    }

    //メモ更新時の動き
    public function update(Request $request)
    {
        $posts = $request->all();
        $request->validate([ 'content' => 'required' ]);

        //トランザクション開始
        DB::transaction(function () use($posts) {
            Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]);

            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();
            foreach($posts['tags'] as $tag){
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag]);
            }

            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])
            ->exists();

            if( (!empty($posts['new_tag']) || $posts['new_tag'] === "0") && !$tag_exists ){

                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);

            }
        });
        //トランザクション終了

        return redirect( route ('home'));
    }

    //メモ論理削除時の動き
    public function destroy(Request $request)
    {
        $posts = $request->all();

        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date("Y-m-d H:i:s", time()) ]);

        return redirect( route ('home'));
    }

}

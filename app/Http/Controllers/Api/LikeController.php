<?php


namespace App\Http\Controllers\Api;


use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LikeController extends BaseController
{
    /**
     * 对某一条评论或者课程进行点赞或取消赞
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $user=$request->user();
        $validator=Validator::make($request->all(),[
            'type_id'=>'required',
        ],[
            'type_id.required'=>'被评论的对象id不能为空'
        ]);
        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }
        $like=Like::where([
            ['type_id','=',$request->input('type_id')],
            ['user_id','=',$user->id]
        ])->first();

        $comment=Comment::where('id',$request->input('type_id'))->first();
        if(!$comment){
            return $this->failed('该评论不存在或已被禁用');
        }
        if($like){
            $is_like = request()->user()->isLike($request->input('type_id'));
            if($is_like == TRUE){ //点过赞,则取消赞
                try{
                    $like->status = 0;
                    $like->save();
                    $comment->decrement('like_num', 1);
                }catch (\Exception $e){
                    return $this->failed($e->getMessage());
                }
                return $this->success(['like' => 0,'like_num'=> $comment->like_num]);  //like=0 或者empty表示取消赞或者未点赞
            }else if($is_like == FALSE) { //取消了赞，则改为点赞
                try {
                    $like->status = 1;
                    $like->save();
                    $comment->increment('like_num', 1);
                }catch (\Exception $e){
                    return $this->failed($e->getMessage());
                }
                return $this->success(['like' => 1,'like_num'=> $comment->like_num]);  //like=1 表示已点赞的状态
            }
        }else{
            try{
                Like::create([
                    'type_id' => $request->input('type_id'),
                    'type'    => $request->input('type') ? $request->input('type') : 1,//1表示给评论点赞，2表示给课程点赞
                    'user_id' => $user->id,
                    'status'  => 1
                ]);
                $comment->increment('like_num',1);
                return $this->success(['like' => 1,'like_num'=> $comment->like_num]);
            }catch (\Exception $e){
                return $this->failed('点赞失败');
            }

        }
    }

}

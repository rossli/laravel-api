<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use App\Models\Course;
use App\Models\CourseMember;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommentController extends BaseController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request){

        $user = $request->user();
        if(!$user){
            return $this->failed('您还未登录!');
        }
        $courseMember=CourseMember::where([  //该用户是否加入该课程
            ['user_id','=',$user->id],
            ['course_id','=', $request->input('course_id')]
        ])->first();
        if(!$courseMember){
            return $this->failed('未学习课程，请学习后再评价');
        }
        $is_comment=$user->isComment($request->input('course_id'));
        if($is_comment){
            return  $this->failed('您已经评论过此课程，请勿重复评论');
        }
        $validator=Validator::make($request->all(),[
            'course_id'  => 'required',
            'practical_score' => 'required',
            'easy_score' => 'required',
            'logic_score' => 'required',
            'content'  => 'required',
        ],[
            'course_id.required'=>'course_id不能为空',
            'practical_score.required'=>'内容实用的内容不能为空',
            'easy_score.required' => '简洁易懂的评分不能为空',
            'logic_score.required' => '逻辑清晰的评分不能为空',
            'content.required'=>'评论内容不能为空',
        ]);
        $score1 = (int)$request->input('practical_score');
        $score2 = (int)$request->input('easy_score');
        $score3 = (int)$request->input('logic_score');
        $scores= number_format(($score1 + $score2 + $score3)/3,'2');
        if ($validator->fails()) {
            return $this->failed($validator->errors()->first());
        }
        $insert_data=[
            'course_id' => $request->input('course_id'),
            'user_id'   => $user->id,
            'practical_score' => $request->input('practical_score'),
            'easy_score'   => $request->input('easy_score'),
            'logic_score'   => $request->input('logic_score'),
            'content'   => $request->input('content'),
            'scores'     =>$scores,
        ];
        try {
            DB::beginTransaction();
            try{
                Comment::create($insert_data);
                $course = Course::findOrFail($request->input('course_id'));
                //计算课程的平均得分跟综合分数
                $course->practical_score = number_format(Comment::where('course_id',$course->id)->avg('practical_score'),'2');
                $course->easy_score = number_format(Comment::where('course_id',$course->id)->avg('easy_score'),'2');
                $course->logic_score = number_format(Comment::where('course_id',$course->id)->avg('logic_score'),'2');
                $course->scores = number_format(Comment::where('course_id',$course->id)->avg('scores'),'2');
                $course->save();
                DB::commit();
            }catch (\Exception $e){
                DB::rollBack();
                return $this->failed('添加失败');
            }
            return $this->success('评论成功');
        } catch (\Exception $e) {
            return $this->failed('添加失败');
        }
    }
}

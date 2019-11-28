@extends('suggest.default')
@section('style')
    <style>

        textarea, input[type='text'] {
            width: 92%;
            border: 0;
            padding: 1rem;
        }

        .title {
            margin: 0;
            padding: 1rem 1rem 0.5rem;
        }

        input[type='radio'] {
            margin-left: 1rem;
        }

        button {
            display: block;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #f00;
            font-size: 16px;
            color: #fff;
            border: none;
            line-height: 2.4rem;
            font-weight: 600;

        }
    </style>
@endsection
@section('content')
    <div class="content">
        <form id="login-form">
            <p class="title">分类标签</p>
            <div class="bg-white">
                <input type="radio" name="type" value="0" checked>功能建议
                <input type="radio" name="type" value="1">性能建议
                <input type="radio" name="type" value="2">Bug反馈
            </div>
            <div>
                <p class="title">反馈内容</p>
                <textarea name="content" placeholder="请输入您要反馈的内容" rows="10"></textarea>
            </div>
            <div>
                <p class="title">联系方式</p>
                <input name="contact" placeholder="QQ、微信、邮箱、手机等联系方式"
                       type="text">
            </div>
            <input type="hidden" name="url" value="{{url()->full()}}">
            <button id="submit" type="button">提交建议</button>
        </form>
    </div>
@endsection
@section('script')
    <script type="text/javascript" src="{{asset('js/jquery-3.4.1.min.js') }}"></script>
    <script>
        $('#submit').click(function () {
            var data = $("#login-form").serialize();

            $.ajax({
                url: "{{route('api.suggest.create')}}",
                type: "POST",
                data: data,
                dataType: 'json',
                success: function (data) {
                    if (data['code'] == 200) {
                        alert('提交成功，谢谢您的反馈')
                    } else {
                        alert(data['message']);
                    }
                },
                error: function (data) {
                    alert('提交失败，请重试');
                }
            });
        });
    </script>
@endsection

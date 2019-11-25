<html>
<header>
    <script type="text/javascript" src="{{asset('js/jquery-3.4.1.min.js') }}"></script>
</header>
<body>
<form id="login-form" action="{{ route('api.suggest.create') }}" method="POST">

    <div>
        姓名:<input name="name" type="text"
                  placeholder="请输入用户名"
                  autocomplete="off" value="">
    </div>
    <div class="input-area ">
        手机号:<input name="mobile" placeholder="请输入手机号"
                   autocomplete="off" value="">
    </div>
    <div>
        建议: <textarea name="content" rows="5" cols="20"></textarea>
    </div>
    <button id="submit" type="button">提交建议</button>
</form>
</body>
</html>
<script>
    $('#submit').click(function () {
        // var name=$("login-form [name='name']").val();
        // var mobile=$("login-form [name='mobile']").val();
        // var content=$("login-form [name='content']").val();
        var data = $("#login-form").serialize();

        $.ajax({
            url:"{{route('api.suggest.create')}}",
            type:"POST",
            data:data,
            dataType:'json',
            success:function (data) {
                if(data['code']==200){
                    alert('提交成功，谢谢您的反馈')
                }else{
                    alert(data['message']);
                }
            },
            error:function (data) {
                alert('提交失败，请重试');
            }
        });
    });
</script>

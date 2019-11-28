@extends('suggest.default')
@section('style')
    <style>
        h2{
            text-align: center;
        }
        li{
            padding: .5rem .5rem 0 0;
            line-height: 1.5rem;
        }
        p{
            margin: 0;
            word-wrap: break-word;
            padding-bottom: 1rem;
        }
        p span{
            display: block;
        }
        .title{
            color: #4694b2;
            white-space: nowrap;
        }

    </style>
@endsection
@section('content')
    <div class="content">
        <h2>意见反馈列表</h2>
        <ul>
            @foreach($suggest as $item)
            <li>
                <p><span class="title">分类: </span><span>{{\App\Models\Suggest::TYPE[$item->type]}}</span></p>
                <p><span class="title">反馈内容:</span><span>{{$item->content}}</span></p>
                <p><span class="title">反馈问题地址:</span> <span><a href="{{$item->url}}">{{$item->url}}</a></span></p>
            </li>
             @endforeach
        </ul>
    </div>
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="token" content="{{csrf_token()}}">
    <title>فعالسازی SPAD</title>
    <style>
        @font-face {
            font-family: iranyekan;
            src: url('./iranyekanwebregular.ttf');
        }
        body{
            height: 100vh;
            width: 100vw;
            margin: 0;
            position: relative;
            background-image: url(./background.png);
            background-blend-mode: color;
            background-color: #00000066;
            background-repeat: no-repeat;
            background-size: cover;
            direction: rtl;
            font-family: iranyekan ;
        }
        .container{
            background-color: white;
            box-shadow: 0px 5px 10px rgb(0 0 0 / 30%);
            position: absolute;
            transform: translate(-50%, -50%);
            left: 50%;
            top: 50%;
            width: 300px;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            font-family: iranyekan ;
        }
        .header{
            padding: 5px;
        }
        img{
            width: 50%;
        }
        input{
            width: 95.5%;
            display: block;
            padding: 7px;
            border: 1px solid rgb(9 132 227 / 32%);
            outline: none;
            border-radius: 5px;
            box-shadow: 0px 5px 5px #cee6fa78;
            margin: 5px 0px 15px 0px;
            font-family: iranyekan ;
        }
        input:focus{
            border: 1px solid rgb(9 132 227 / 87%);
        }
        label{
            display: block;
            text-align: right;
            font-family: iranyekan ;
        }
        .muted{
            color: gray;
            text-decoration: none;
        }
        .footer{
            padding: 10px 0px 0px 0px;
            border-top: 1px solid #0000002e;
            margin-top: 15px;
        }
        button{
            background-color: #0984e3;
            border: 1px solid #0567b2;
            width: 100%;
            color: #fff;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
            transition: .3s all;
            font-family: iranyekan ;
        }
        button:hover{
            background-color: #0769b4;
            box-shadow: 0px 0px 5px #0769b459;
            transition: .3s all;
        }
        @media only screen and (max-width: 350px){
            .container{
                background-color: white;
                box-shadow: 0px 5px 10px rgb(0 0 0 / 30%);
                position: absolute;
                transform: translate(-50%, -50%);
                left: 50%;
                top: 50%;
                width: 300px;
                padding: 20px;
                text-align: center;
                border-radius: 10px;
            }
        }
        .error{
            background: #f4dada;
            color: #292c23;
            padding: 15px 6px;
            margin: 22px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="https://panel.spad.estate/storage/settings/logopng.png">
    </div>
    <div class="body">
        <form method="post">
            @csrf
            @if(isset($error))
                <div class="error">{{$error}}</div>
            @endif
            <div>
                <label for="username">نام کاربری</label>
                <input id="username" name="username" value="{{old('username')}}"/>
            </div>
            <div>
                <label for="id_order">کد سفارش</label>
                <input id="id_order" name="id_order" value="{{old('id_order')}}"/>
            </div>
            <div>
                <button type="submit">فعالسازی</button>
            </div>
        </form>
    </div>
    <div class="footer">
        <a class="muted" href="#">SPAD</a>
    </div>
</div>
</body>
</html>

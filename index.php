<html>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <link rel="shortcut icon" href="https://github.githubassets.com/favicons/favicon-dark.png"/>
    <title>Chatbot</title>
    <script src="https://code.jquery.com/jquery-1.12.4.js" integrity="sha256-Qw82+bXyGq6MydymqBxNPYTaUXXq7c8v3CwiYwLLNXU=" crossorigin="anonymous"></script>
</head>
<style type="text/css">
* {
	padding: 0px;
	margin: 0px;
}

#body {
	background-image: linear-gradient(to right, rgb(18, 17, 17) , rgb(15, 17, 19));
	background-size: cover;
	height: 100%;
	width: 100%;
}

#box1 {
	height: 75%;
	width: 80%;
	display: block;
}

#box2 {
	height: 25%;
	width: 80%;
}

h2 {
	height: 10%;
}

a {
	color: white;
	text-decoration: none;
	font-size: 34px;
}

a:hover {
	text-shadow: 2px 2px 2px rgb(120, 120, 120);
}


.chatBox {
	cursor: pointer;
	display: inline-block;
	background: rgba(55,55,55,0.5);
	height: 50%;
	width: 90%;
	overflow: auto;
	text-align: left;
	text-indent: 10%;
	color: white;
	text-shadow: 1px 1px 1px black;
	font-size: 20px;
	padding-top: 20px;
}

form {
	height: 100%;
	widows: 100%;
}
#userInput {
	display:inline-block;
	width: 50%;
	height: 30%;
	font-size: 24px;
	background: rgba(55,55,55,0.5);
	outline:0;
	border:3px rgba(155,155,155,0.3) solid;
	border-left:5px rgba(22,22,22,0.9) solid;
	box-shadow:5px 5px 5px black;
	color:rgba(200,200,200,0.8);
	text-decoration: none;
	text-indent: 0.5em;
}
#send, #clean {
	display:inline-block;
	width: 15%;
	height: 30%;
	font-size: 24px;
	background: rgba(150, 150, 150, 0.3);
	outline:0;
	color:white;
	border:3px rgba(155,155,155,0.5) solid;
	box-shadow:5px 5px 5px black;
	cursor: pointer;
	text-shadow: 1px 1px 1px black;
	text-overflow: hidden;
}
</style>
<body id="body">
<center>
    <div id="box1">
        <br>
        <br>
        <h2><a target="_blank" href="https://github.com/kompasim/chatbot">Chatbot</a></h2>
        <br>
        <br>
        <div class="chatBox">
            welcome , i am chatbot ...
        </div>
        <div>
            <br>
            <a target="_blank" href="api.php?requestType=talk&userInput=ياخشىمۇسىز">api</a>
        </div>
    </div>
    <div id="box2" class="userMessage">
        <form id="fMessage">
            <input id="clean" type="button" class="clean" value="clean"/>
            <input type="text" name="userInput" id="userInput"/>
            <input id="send" type="submit" value="send" class="send"/>
        </form>
    </div>
</center>
</body>
<script type="text/javascript">
    $(document).ready(function () {

        var localUrl = window.location.href;
        localUrl = localUrl.replace('web/', '');

        // só um advinhador simples
        var webServiceUrl = window.location.href + 'api.php';
        console.log(webServiceUrl);
        $('.clean').click(function () {

            Clear();
            AddText('system ', 'cleaning...');

            $('.userMessage').hide();

            $.ajax({
                type: "GET",
                url: webServiceUrl,
                data: {
                    requestType: 'forget'
                },
                success: function (response) {
                    AddText('system ', 'Ok!');
                    $('.userMessage').show();
                },
                error: function (request, status, error) {
                    Clear();
                    alert('error');
                    $('.userMessage').show();
                }
            });
        });


        $('#fMessage').submit(function () {

            // get user input
            var userInput = $('input[name="userInput"]').val();

            // basic check
            if (userInput == '')
                return false;
            //

            // clear
            $('input[name="userInput"]').val('');

            // hide button
            $(this).hide();

            // show user input
            AddText('A ', userInput);

            $.ajax({
                type: "GET",
                url: webServiceUrl,
                data: {
                    userInput: userInput,
                    requestType: 'talk'
                },
                success: function (response) {
                    console.log(webServiceUrl);
                    console.log(userInput);
                    AddText('B ', response.message);
                    $('#fMessage').show();
                    $('input[name="userInput"]').focus();
                },
                error: function (request, status, error) {
                    console.log(error);
                    alert('error');
                    $('#fMessage').show();
                }
            });

            return false;
        });

        function Clear() {
            $('.chatBox').html('');
        }

        function AddText(user, message) {
            console.log(user);
            console.log(message);

            var div = $('<div>');
            var name = $('<labe>').addClass('name');
            var text = $('<span>').addClass('message');

            name.text(user + ':');
            text.text('\t' + message);

            div.append(name);
            div.append(text);

            $('.chatBox').append(div);

            $('.chatBox').scrollTop($(".chatBox").scrollTop() + 100);
        }


    });
</script>
</html>
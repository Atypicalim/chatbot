<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
		<link rel="shortcut icon" href="favicon.ico" />
		<title>AIML 2.5 Web Test</title>
		<script src="js/jquery.js" type="text/javascript"></script>
		<link rel="stylesheet" href="css/main.css" type="text/css" />
		
		<script type="text/javascript">
			$(document).ready(function(){

				var localUrl = window.location.href;
				localUrl = localUrl.replace('web/', '');

				// só um advinhador simples
				var webServiceUrl 	= window.location.href.replace('web/', 'api.php');

				$('.clean').click(function(){

					Clear();
					AddText('system ', 'cleaning...');
					
					$('.userMessage').hide();

					$.ajax({
						  type: "GET",
						  url: webServiceUrl,
						  data: {
							  	requestType:'forget'
						  	},
						  success: function(response){
							  AddText('system ', 'Ok!');
							  $('.userMessage').show();
						  },
						  error: function(request, status, error)
						  {
							  Clear();
							  alert('error');
							  $('.userMessage').show();
						  }
						});
				});
				
				
				$('#fMessage').submit(function(){
					
					// get user input
					var userInput = $('input[name="userInput"]').val();
					
					// basic check
					if(userInput == '')
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
						  	input:userInput,
						  	requestType:'talk'
					  	},
					  success: function(response){
						  AddText('B ', response.message);
						  $('#fMessage').show();
						  $('input[name="userInput"]').focus();
					  },
					  error: function(request, status, error)
					  {
					  	  console.log(error);
						  alert('error');
						  $('#fMessage').show();
					  }
					});

					return false;
				});

				function Clear()
				{
					$('.chatBox').html('');
				}
				
				function AddText(user, message)
				{
					console.log(user);
					console.log(message);

					var div 	= $('<div>');
					var name	= $('<labe>').addClass('name');
					var text	= $('<span>').addClass('message');
					
					name.text(user + ':');
					text.text('\t' + message);
					
					div.append(name);
					div.append(text);
					
					$('.chatBox').append(div);
					
					$('.chatBox').scrollTop($(".chatBox").scrollTop() + 100);
				}
				
				
				
				
			});
		</script>
	</head>
	<body id="body">
		<center>
			<div id="box1">
				<br>
				<br>
				<h2><a target="_blank" href="https://github.com/kompasim/chatbot-utf8">Chatbot</a></h2>
				<br>
				<br>
				<div class="chatBox">
					welcome , i am chatbot ...
				</div>
				<div>
					<br>
					<a target="_blank" href="api.php?requestType=talk&input=ياخشىمۇسىز">api</a>
				</div>
			</div>
			<div id="box2" class="userMessage">
				<form id="fMessage">
					<input id="clean" type="button" class="clean" value="clean" />
					<input type="text" name="userInput" id="userInput"/>
					<input id="send" type="submit" value="send" class="send"/>
				</form>
			</div>
		</center>
	</body>


</html>
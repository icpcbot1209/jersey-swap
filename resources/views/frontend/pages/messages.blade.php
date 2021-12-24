@extends ('../../layouts/app')
@section('title')
    Messages - Jersey Swap
@endsection
@section('content')
   <section id="heading" class="mt-5">
     <div class="container">
          <div class="row" style="border: 1px solid gray; border-radius: 8px; height: 600px; margin-bottom: 10px;">
            <div class="col-md-3" style="height: 100%; overflow-y: scroll; display: flex; flex-direction: column;">
              <input id="search_input" class="form-control" placeholder="Type a username" style="margin-top: 8px;"/>
              <div id="search_result"></div>
              @foreach($user_list as $user)
                <div style="display: flex; flex-flow: row nowrap; margin-top: 8px; cursor: pointer;"
                onclick="onClickUser('{{$user->username}}', '{{$user->id}}')">
                  <img src="{{ asset($user->profile_picture) }}" class="table-user-thumb" alt="">
                  <div style="display: flex; align-items: center; margin-left: 4px;">{{$user->username}}</div>
                </div>
              @endforeach
            </div>
            <div class="col-md-9" style="height: 100%; padding: 4px; display: flex; flex-direction: column;">
              <span id="chat_with" style="margin: 10px; font-weight: bold;"></span>
              <div id="chat_output" style="flex: 1; overflow-y: scroll; padding: 4px; margin: 4px; display: flex; flex-direction:column;">
              </div>
              <div style="display: flex; flex-flow: row nowrap;">
                <input id="chat_input" class="form-control" placeholder="Type a message and press Enter"/>
                <button type="button" class="btn">
                  <i class="fa fa-paperclip fa-lg" aria-hidden="true"></i>
                </button>
                <button id="sendBtn" type="button" class="btn">
                  <i class="fa fa-paper-plane fa-lg" aria-hidden="true"></i>
                </button>
              </div>
            </div>
          </div>
      </div>
  </section>
@endsection
@section('custom-scripts')
  <script>
    var sendTo=0;
    $(document).ready(function() {
      var userList = {!! json_encode($user_list) !!};
      console.log(userList);
      if(userList.length>0){
        sendTo= userList[0].id;
        onClickUser(userList[0].username, userList[0].id);
      } 
      $("#chat_output").animate({scrollTop: $('#chat_output').prop("scrollHeight")}, 1000);
    })

    function onClickUser(userName, userId){
      $("#chat_with").text("Chat with "+ userName);
      var chatOutput = "";
      var message_with= parseInt(userId);
      console.log(message_with);
      sendTo= message_with;
      $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
      });
      $.ajax({
          url: "{{url('/messages/get_by_user')}}",
          method: 'post',
          data : {
              message_with: message_with
          },
          success: function(result){
              result.messages.forEach((item)=>{

                  var span = document.createElement('span');
                  span.innerHTML = item.message_content;

                  var dateSpan = document.createElement('span');
                  var date = new Date(item.created_at);
                  var options = { month: 'short'};
                  var month = new Intl.DateTimeFormat('en-US', options).format(date);
                  dateSpan.innerHTML = month + " " + date.getDate() + " " + date.getFullYear() + ", " + ("0" + date.getHours()).slice(-2)  + ":" + ("0" + date.getMinutes()).slice(-2);

                  if(item.sent_from == message_with){
                    span.style.cssText = 'background: #f2f6f9; border-radius: 4px; padding: 8px;margin-top:2px; width:fit-content;';
                    dateSpan.style.cssText = 'margin-top:4px; color: grey; font-size: small;';
                  }else{
                    span.style.cssText = 'text-align: right; background: #dbf1ff; border-radius: 4px; padding: 8px;margin-top:2px; width:fit-content; margin-left: auto';
                    dateSpan.style.cssText = 'text-align: right; margin-top:4px; color: grey; font-size: small;';
                  }



                  $("#chat_output").append(dateSpan);
                  $("#chat_output").append(span);
              });
              $("#chat_output").animate({scrollTop: $('#chat_output').prop("scrollHeight")}, 1000);

          },
          error: function (request, status, error) {
            console.log(error);
          }
      });

      $("#chat_output").html(chatOutput);
      
      sendTo= userId;
    }

    $("#search_input").bind('input', function() { 
        keyword = $(this).val();
        $("#search_result").empty();
        if(keyword.length<4) return;
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            url: "{{url('/messages/get_users')}}",
            method: 'post',
            data : {
                keyword: keyword
            },
            success: function(result){
              result.users.forEach((item)=>{

                  var userDiv = $("<div style='display: flex; flex-flow: row nowrap; margin-top: 8px; cursor: pointer;'></div>");
                  
                  userDiv.click(function() {
                    console.log("userdiv clicked...");
                    onClickUser(item.username, item.id);
                  });

                  var img = document.createElement('img');
                  img.classList.add('table-user-thumb');
                  img.setAttribute('src',base_url+'/'+item.profile_picture);
                  userDiv.append(img);

                  var usernameDiv = $("<div style='display: flex; align-items: center; margin-left: 4px;'>"+item.username+"</div>");
                  userDiv.append(usernameDiv);

                  $("#search_result").append(userDiv);
              });
            },
            error: function (request, status, error) {
              console.log(error);
            }
        });
      });
    let ws = new WebSocket('ws://jerseyswaponline.com:8090');
    console.log({{auth()->id()}});
    ws.onopen = function (e) {
        // Connect to websocket
        console.log('Connected to websocket');
        ws.send(
            JSON.stringify({
                'type': 'socket',
                'user_id': '{{auth()->id()}}'
            })
        );

        // Bind onkeyup event after connection
        $('#chat_input').on('keyup', function (e) {
            if(sendTo==0) return;
            if (e.keyCode === 13 && !e.shiftKey) {
                let message_content = $(this).val();
                ws.send(
                    JSON.stringify({
                        'type': 'chat',
                        'from': '{{auth()->id()}}',
                        'to': sendTo,
                        'message_content': message_content
                    })
                );
                $(this).val('');
                console.log('{{auth()->id()}} sent ' + message_content);
            }
        });
        $('#sendBtn').click(function (e) {
            if(sendTo==0) return;
            console.log('its clicked...');
            let message_content = $('#chat_input').val();
            ws.send(
                JSON.stringify({
                    'type': 'chat',
                    'from': '{{auth()->id()}}',
                    'to': sendTo,
                    'message_content': message_content
                })
            );
            $('#chat_input').val('');
            console.log('{{auth()->id()}} sent ' + message_content);
        });
    };
    ws.onerror = function (e) {
        // Error handling
        console.log(e);
        alert('Check if WebSocket server is running!');
    };
    ws.onclose = function(e) {
        console.log(e);
        alert('Check if WebSocket server is running!');
    };
    ws.onmessage = function (e) {
      console.trace(e);
        let json = JSON.parse(e.data);
        switch (json.type) {
            case 'chat':
                var span = document.createElement('span');
                span.innerHTML = json.msg;

                var dateSpan = document.createElement('span');
                var dateNow = Date.now();
                var date = new Date(dateNow);

                var options = { month: 'short'};
                var month = new Intl.DateTimeFormat('en-US', options).format(date);
                dateSpan.innerHTML = month + " " + date.getDate() + " " + date.getFullYear() + ", " + ("0" + date.getHours()).slice(-2)  + ":" + ("0" + date.getMinutes()).slice(-2);

                if(json.from == 'me'){
                  span.style.cssText = 'text-align: right; background: #dbf1ff; border-radius: 4px; padding: 8px;margin-top:2px; width:fit-content; margin-left: auto';
                  dateSpan.style.cssText = 'text-align: right; margin-top:4px; color: grey; font-size: small;';
                }else{
                  span.style.cssText = 'background: #f2f6f9; border-radius: 4px; padding: 8px;margin-top:2px; width:fit-content;';
                  dateSpan.style.cssText = 'margin-top:4px; color: grey; font-size: small;';
                }

                $('#chat_output').append(dateSpan); // Append the new message received
                $('#chat_output').append(span); // Append the new message received
                $("#chat_output").animate({scrollTop: $('#chat_output').prop("scrollHeight")}, 1000); // Scroll the chat output div
                console.log("Received " + json.msg);
                break;

            case 'socket':
                $('#total_client').html(json.msg);
                console.log("Received " + json.msg);
                break;
        }
    };
  </script>
@endsection

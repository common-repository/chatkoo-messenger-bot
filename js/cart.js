window.fbAsyncInit = function() {
    FB.init({
        appId      : '1603012233332669',
        xfbml      : true,
        version    : 'v2.6'
    });

    FB.getLoginStatus(function(response) {
      if (response.status === 'connected') {
          uid = response.authResponse.userID;
          ref["uid"] = uid;
      } else if (response.status === 'not_authorized') {
          uid = "not_authorized";
      } else {
          uid = "null";
      }
     });

    FB.Event.subscribe('messenger_checkbox', function(e) {
        console.log("messenger_checkbox event");
        console.log(e);

        if (e.event == 'rendered') {
            console.log("Plugin was rendered");
            FB.api('/me', {fields: 'first_name, last_name'}, function(response) {
                ref["first_name"] = response["first_name"];
                ref["last_name"] = response["last_name"];
            });
        } else if (e.event == 'checkbox') {
            var checkboxState = e.state;
            if (checkboxState == "checked"){
                document.cookie = "user_ref="+user_ref+";path=/";
            }
            console.log("Checkbox state: " + checkboxState);
        } else if (e.event == 'not_you') {
            console.log("User clicked 'not you'");
        } else if (e.event == 'hidden') {
            // window.location.reload();
            console.log("Plugin was hidden");
        }
    });
};

(function(d, s, id){
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {return;}
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk')
);

function confirmOptIn() {
    FB.AppEvents.logEvent('MessengerCheckboxUserConfirmation', null, {
        'app_id':1603012233332669,
        'page_id':page_id,
        'ref':JSON.stringify(ref),
        'user_ref':user_ref
    });
    console.log("Opted in");
}

jQuery(function($){
    var snippet = "Receive updates in messenger<br><div class=\"fb-messenger-checkbox\" origin="+url+" page_id="+page_id+" messenger_app_id=1603012233332669 user_ref=\""+user_ref+"\" allow_login=\"true\" size=\"large\"> </div><br>"
    var get_hash = window.location.hash
    if (get_hash != "#checkbox_false"){
        $(".wc-proceed-to-checkout").prepend(snippet)
    }
    $('.checkout-button').click(function(){
        confirmOptIn();
    })
})

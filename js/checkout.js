function getCookieValue(a) {
    var b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
    return b ? b.pop() : '';
}

var cart_session = window.location.hash.split("_")[1]

if (cart_session == undefined){
    cart_session = getCookieValue("user_session")
}

var node = document.createElement("input")
node.setAttribute("type", "text")
node.setAttribute("class", "input-text")
node.setAttribute("name", "cart_session")
node.setAttribute("id", "cart_session")
node.setAttribute("value", cart_session)
document.getElementById("user_session_field").appendChild(node);

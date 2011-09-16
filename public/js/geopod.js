var geopod = {
    
    setSize: function(params) {
        if( typeof params != "object" ) {
            params = {};
        }

        var obj = {};
        var height = params.height;
        var width = params.width;

        obj.method = 'setSize';
        if( height )
            obj.height = height;
        if( width )
            obj.width = width;

        geopod.sendMessage(obj);
    },
    sendMessage: function(obj) {
        var host = geopod._getHost(document.location.hash.slice(1));
        if( window.postMessage ) {
            window.parent.postMessage(geopod._urlencode(obj), host);
        }
        else {
            var iframe = document.createElement("frame");

            iframe.src = host + "/app/xss/?" + geopod._urlencode(obj);
            var root = document.getElementById('geopod-root');

            if( root == undefined ) {
                var root = document.createElement("div");
                root.id = "geopod-root";
                document.body.appendChild(root);
                root = document.getElementById('geopod-root');
            }

            root.appendChild(iframe);
            setTimeout("document.getElementById('geopod-root').innerHTML=''", 2000);
        }
    },
    _urlencode: function(obj) {
        var str = [];
        for( var p in obj ) {
            str.push(p + "=" + encodeURIComponent(obj[p]));
        }
        return str.join("&");
    },
    _getHost: function(url) {
        url = url.replace("http://", "");

        var urlExplode = url.split("/");
        var serverName = urlExplode[0];

        serverName = 'http://'+serverName;
        return serverName;
    }
    
};
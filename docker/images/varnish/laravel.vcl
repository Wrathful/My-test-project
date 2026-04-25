# VCL version 5.0 is not supported so it should be 4.0 even though actually used Varnish version is 6
vcl 4.1;

import std;
import directors;
import bodyaccess;

# The minimal Varnish version is 6.0
# For SSL offloading, pass the following header in your proxy server or load balancer: 'X-Forwarded-Proto: https'

backend server1 {
    .host = "php";
    .port = "80";
    .first_byte_timeout = 600s;
    .probe = {
        .url = "/health_check.php";
        .timeout = 2s;
        .interval = 1m;
        .window = 10;
        .threshold = 5;
    }
}

sub vcl_init {
    new prod = directors.round_robin();
    prod.add_backend(server1);
}

acl purge {
    "localhost";
    "0.0.0.0/0";
}

sub vcl_recv {

    unset req.http.X-Body-Len; # for post query

    # send all traffic to the prod director:
    set req.backend_hint = prod.backend();

    if (req.restarts > 0) {
        set req.hash_always_miss = true;
    }

    if (req.method == "PURGE") {
        if (client.ip !~ purge) {
            return (synth(405, "Method not allowed"));
        }
        if (!req.http.X-Cache-Tags-Pattern) {
            return (synth(400, "X-Cache-Tags-Pattern header required"));
        }
        if (req.http.X-Cache-Tags-Pattern) {
          ban("obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags-Pattern);
        }
        return (synth(200, "Purged"));
    }

    if (req.method != "GET" &&
        req.method != "HEAD" &&
        req.method != "PUT" &&
        req.method != "POST" &&
        req.method != "TRACE" &&
        req.method != "OPTIONS" &&
        req.method != "DELETE") {
          /* Non-RFC2616 or CONNECT which is weird. */
          return (pipe);
    }

    if (req.method != "GET" && req.method != "HEAD" && req.method != "POST") {
        return (pass);
    }

    # Bypass customer, shopping cart, checkout
    if (req.url !~ "/graphql.php") {
        return (pass);
    }

    # Bypass health check requests
    if (req.url ~ "^/health_check.php$") {
        return (pass);
    }

    # Set initial grace period usage status
    set req.http.grace = "none";

    # normalize url in case of leading HTTP scheme and domain
    set req.url = regsub(req.url, "^http[s]?://", "");

    # collect all cookies
    std.collect(req.http.Cookie);

    # Compression filter. See https://www.varnish-cache.org/trac/wiki/FAQ/Compression
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv)$") {
            # No point in compressing these
            unset req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate" && req.http.user-agent !~ "MSIE") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            # unknown algorithm
            unset req.http.Accept-Encoding;
        }
    }

    if (req.method == "POST") {
        if (std.integer(req.http.content-length, 0) > 500000) {
            return(synth(413, "The request body size exceeds the limit"));
        }
        if(!std.cache_req_body(500KB)){
            return(hash);
        }
        set req.http.X-Body-Len = bodyaccess.len_req_body();
    }

    return (hash);
}

sub vcl_hash {

    # To make sure http users don't see ssl warning
    if (req.http.SSL-OFFLOADED) {
        hash_data(req.http.SSL-OFFLOADED);
    }

    # To cache POST and PUT requests
    if (req.http.X-Body-Len) {
        bodyaccess.hash_req_body();
    } else {
        hash_data("");
    }
}


sub vcl_backend_response {

    set beresp.grace = 3d;

    #if (bereq.url ~ "\.js$" || beresp.http.content-type ~ "text") {
    #    set beresp.do_gzip = true;
    #}

    if (beresp.http.X-Cache-Debug) {
        set beresp.http.X-Debug-Cache-Control = beresp.http.Cache-Control;
    }

    # cache only successfully responses and 404s that are not marked as private
    if (beresp.status != 200) {
        set beresp.uncacheable = true;
        set beresp.ttl = 86400s;
        return (deliver);
    }

    # validate if we need to cache it and prevent from setting cookie
    if (beresp.ttl > 0s && (bereq.method == "GET" || bereq.method == "HEAD" || bereq.method == "POST")) {
        unset beresp.http.set-cookie;
    }

   # If page is not cacheable then bypass varnish for 2 minutes as Hit-For-Pass
   if (beresp.ttl <= 0s ||
       beresp.http.Surrogate-control ~ "no-store" ||
       (!beresp.http.Surrogate-Control &&
       beresp.http.Cache-Control ~ "no-cache|no-store") ||
       beresp.http.Vary == "*") {
        # Mark as Hit-For-Pass for the next 2 minutes
        set beresp.ttl = 120s;
        set beresp.uncacheable = true;
   }
    return (deliver);
}

sub vcl_deliver {
    #if (resp.http.x-varnish ~ " ") {
    #    set resp.http.X-Magento-Cache-Debug = "HIT";
    #    set resp.http.Grace = req.http.grace;
    #} else {
    #    set resp.http.X-Magento-Cache-Debug = "MISS";
    #}

    # Not letting browser to cache non-static files.
    if (resp.http.Cache-Control !~ "private" && req.url !~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv)$") {
        set resp.http.Pragma = "no-cache";
        set resp.http.Expires = "-1";
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }

    if (!resp.http.X-Cache-Debug) {
        unset resp.http.Age;
        unset resp.http.X-Cache-Tags;
    } else {
        if (obj.hits > 0) {
          set resp.http.X-Cache = "HIT";
        } else {
          set resp.http.X-Cache = "MISS";
        }
    }
    unset resp.http.X-Cache-Debug;
    unset resp.http.X-Powered-By;
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Link;
}

sub vcl_hit {
    if (obj.ttl >= 0s) {
        # Hit within TTL period
        return (deliver);
    }
    if (std.healthy(req.backend_hint)) {
        if (obj.ttl + 300s > 0s) {
            # Hit after TTL expiration, but within grace period
            set req.http.grace = "normal (healthy server)";
            return (deliver);
        } else {
            # Hit after TTL and grace expiration
            return (restart);
        }
    } else {
        # server is not healthy, retrieve from cache
        set req.http.grace = "unlimited (unhealthy server)";
        return (deliver);
    }
}

sub vcl_backend_fetch {
    if (bereq.http.X-Body-Len) {
        set bereq.method = "POST";
    }
}

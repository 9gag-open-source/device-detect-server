# Device Detection server

Extremely simple API server providing Device Detection, based on the [Piwik Device Detector](https://github.com/piwik/device-detector)

## Usage

The server is provided as a docker container. To start the server:
```
docker run -p 80:80 derekcsy/device-detect-server
```

The server implements a single endpoint at `v1/detect?ua=${USER_AGENT}` and returns detection results in JSON.

## Examples:

Desktop browser:
```
$ curl -s http://localhost/v1/detect?ua=Mozilla%2F5.0%20%28X11%3B%20Linux%20x86_64%29%20AppleWebKit%2F537.36%20%28KHTML%2C%20like%20Gecko%29%20Chrome%2F51.0.2704.103%20Safari%2F537.36. | jq .
{
  "client": {
    "type": "browser",
    "name": "Chrome",
    "short_name": "CH",
    "version": "51.0",
    "engine": "Blink",
    "engine_version": ""
  },
  "browser_family": "Chrome",
  "os": {
    "name": "GNU/Linux",
    "short_name": "LIN",
    "version": "",
    "platform": "x64"
  },
  "os_family": "GNU/Linux",
  "device": {
    "type": "desktop",
    "brand": "",
    "model": ""
  },
  "bot": null
}
```

Mobile user-agent example:
```
$ curl -s http://localhost/v1/detect?ua=Mozilla%2F5.0%20%28iPhone%3B%20CPU%20iPhone%20OS%2010_0_1%20like%20Mac%20OS%20X%29%20AppleWebKit%2F602.1.50%20%28KHTML%2C%20like%20Gecko%29%20Version%2F10.0%20Mobile%2F14A403%20Safari%2F602.1 | jq
{
  "client": {
    "type": "browser",
    "name": "Mobile Safari",
    "short_name": "MF",
    "version": "10.0",
    "engine": "WebKit",
    "engine_version": "602.1.50"
  },
  "browser_family": "Safari",
  "os": {
    "name": "iOS",
    "short_name": "IOS",
    "version": "10.0",
    "platform": ""
  },
  "os_family": "iOS",
  "device": {
    "type": "smartphone",
    "brand": "AP",
    "model": "iPhone"
  },
  "bot": null
}
```

Bot user-agent example:
```
$ curl -s http://localhost/v1/detect?ua=Mozilla%2F5.0%20%28compatible%3B%20Googlebot%2F2.1%3B%20%2Bhttp%3A%2F%2Fwww.google.com%2Fbot.html%29 | jq
{
  "client": null,
  "browser_family": "Unknown",
  "os": null,
  "os_family": "Unknown",
  "device": {
    "type": "",
    "brand": "",
    "model": ""
  },
  "bot": {
    "name": "Googlebot",
    "category": "Search bot",
    "url": "http://www.google.com/bot.html",
    "producer": {
      "name": "Google Inc.",
      "url": "http://www.google.com"
    }
  }
}
```

/**
 * Copyright 2018 Google Inc. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *     http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// If the loader is already loaded, just stop.
if (!self.define) {
  let registry = {};

  // Used for `eval` and `importScripts` where we can't get script URL by other means.
  // In both cases, it's safe to use a global var because those functions are synchronous.
  let nextDefineUri;

  const singleRequire = (uri, parentUri) => {
    uri = new URL(uri + ".js", parentUri).href;
    return registry[uri] || (
      
        new Promise(resolve => {
          if ("document" in self) {
            const script = document.createElement("script");
            script.src = uri;
            script.onload = resolve;
            document.head.appendChild(script);
          } else {
            nextDefineUri = uri;
            importScripts(uri);
            resolve();
          }
        })
      
      .then(() => {
        let promise = registry[uri];
        if (!promise) {
          throw new Error(`Module ${uri} didn’t register its module`);
        }
        return promise;
      })
    );
  };

  self.define = (depsNames, factory) => {
    const uri = nextDefineUri || ("document" in self ? document.currentScript.src : "") || location.href;
    if (registry[uri]) {
      // Module is already loading or loaded.
      return;
    }
    let exports = {};
    const require = depUri => singleRequire(depUri, uri);
    const specialDeps = {
      module: { uri },
      exports,
      require
    };
    registry[uri] = Promise.all(depsNames.map(
      depName => specialDeps[depName] || require(depName)
    )).then(deps => {
      factory(...deps);
      return exports;
    });
  };
}
define(['./workbox-7e5eb42b'], (function (workbox) { 'use strict';

  self.skipWaiting();
  workbox.clientsClaim();
  /**
   * The precacheAndRoute() method efficiently caches and responds to
   * requests for URLs in the manifest.
   * See https://goo.gl/S9QRab
   */
  workbox.precacheAndRoute([{
    "url": "registerSW.js",
    "revision": "1872c500de691dce40960bb85481de07"
  }, {
    "url": "index.html",
    "revision": "a71edf680fe3bb6bc21d8e80ee0661ee"
  }, {
    "url": "bundles/apiplatform/style.css",
    "revision": "ef7a5a78b62b01bfd417a81addef3622"
  }, {
    "url": "bundles/apiplatform/init-swagger-ui.js",
    "revision": "c2bb4b98ba8d1cca13dec959cc852315"
  }, {
    "url": "bundles/apiplatform/init-scalar-ui.js",
    "revision": "15b80ea99aaa02be78099aefeec3a873"
  }, {
    "url": "bundles/apiplatform/init-redoc-ui.js",
    "revision": "e5d3783b08ae6bfc74af5145acab655d"
  }, {
    "url": "bundles/apiplatform/init-graphiql.js",
    "revision": "1dd3ac13e586e70c7a5e384ac5f57620"
  }, {
    "url": "bundles/apiplatform/init-common-ui.js",
    "revision": "70530e358bb77ae10962d8adfe9a5b96"
  }, {
    "url": "bundles/apiplatform/graphiql-style.css",
    "revision": "1c95ea1fbdc8d4797ac78f6f218f4d39"
  }, {
    "url": "bundles/apiplatform/swagger-ui/swagger-ui.css",
    "revision": "6c89a7b56731679f8e6fbbe97762408d"
  }, {
    "url": "bundles/apiplatform/swagger-ui/swagger-ui-standalone-preset.js",
    "revision": "7289ecfaa5507c519cb95606e6216dbb"
  }, {
    "url": "bundles/apiplatform/swagger-ui/swagger-ui-bundle.js",
    "revision": "f1a737f742dd692d123be959f22f726f"
  }, {
    "url": "bundles/apiplatform/swagger-ui/oauth2-redirect.js",
    "revision": "d170ff78b648354d9005ff7dce2b141d"
  }, {
    "url": "bundles/apiplatform/swagger-ui/oauth2-redirect.html",
    "revision": "b7645a1518d12a4231b345b513aa7606"
  }, {
    "url": "bundles/apiplatform/redoc/redoc.standalone.js",
    "revision": "782e0f833fffc4317c21459229890a18"
  }, {
    "url": "bundles/apiplatform/fonts/open-sans/700.css",
    "revision": "5b52734f2fe5eb3c4685617cc43aaffa"
  }, {
    "url": "bundles/apiplatform/fonts/open-sans/400.css",
    "revision": "3ea3483ef518ce5b654419c940a758ab"
  }, {
    "url": "build/app.js",
    "revision": "442082c4f296c01a5fae66603a4942af"
  }, {
    "url": "build/app.css",
    "revision": "048ada4288b2f10b5aa9bdc55ae5526a"
  }, {
    "url": "apple-touch-icon.png",
    "revision": "c8efb21a33bff7b75f235edf76d89c00"
  }, {
    "url": "favicon-16x16.png",
    "revision": "029ad2e501b4ed446fef260a1409dd3d"
  }, {
    "url": "favicon-32x32.png",
    "revision": "d97308ddea88e729a66f89bbfe0b3626"
  }, {
    "url": "favicon.ico",
    "revision": "5fbaf2583e3ae2f254c4229969d61bd3"
  }, {
    "url": "icons/icon-192.png",
    "revision": "bea2f2a05bee976b5be141bb9ed64bc7"
  }, {
    "url": "icons/icon-512-maskable.png",
    "revision": "ab1bd0a668c8811f934014234290a6ba"
  }, {
    "url": "icons/icon-512.png",
    "revision": "ddf95b814cb0fc0e77f222107ac7c24c"
  }, {
    "url": "manifest.webmanifest",
    "revision": "96fca35ca328bbf91bfd2b717cb2c8b9"
  }], {});
  workbox.cleanupOutdatedCaches();
  workbox.registerRoute(new workbox.NavigationRoute(workbox.createHandlerBoundToURL("index.html")));

}));

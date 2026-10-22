// Ticks for emergency-alert.js. Browsers slow page timers in background tabs to about once a
// minute after a few minutes; worker timers keep their pace, so alerts still arrive within seconds.
let timer = null;

self.onmessage = (e) => {
    clearInterval(timer);
    timer = setInterval(() => self.postMessage('tick'), Number(e.data) || 5000);
};

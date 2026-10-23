// Feed JSON from php tests/pickers.php. Real rendered scripts, fake opener/DOM.
import assert from 'node:assert/strict';
import vm from 'node:vm';
let input = '';
for await (const chunk of process.stdin) input += chunk;
const pages = JSON.parse(input);
let checks = 0;
for (const [template, callbacks] of [
  ['image_browse', [['img_callback', '/uploads/fixture/caf%C3%A9.jpg?x=1&y=2']]],
  ['media_browse', [['me_callback', '/uploads/fixture/file.pdf'], ['pg_callback', '/_page17']]],
]) {
  const html = pages[template];
  assert.doesNotMatch(html, /jquery|cdnjs|\$\.noConflict/i);
  const calls = [];
  let closes = 0;
  const context = vm.createContext({
    window: {
      opener: {CKEDITOR: {tools: {callFunction: (...args) => calls.push(args)}}},
      close: () => closes++,
    },
    document: {
      getElementById: id => ({value: id === 'bodyimage' ? callbacks[0][1] : '/uploads/fixture/file.pdf'}),
      querySelector: selector => {
        assert.equal(selector, 'select[name=pages]');
        return {value: '17'};
      },
    },
  });
  for (const [, script] of html.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)) {
    if (script.trim()) vm.runInContext(script, context);
  }
  for (const [callback, expected] of callbacks) {
    vm.runInContext(`${callback}()`, context);
    assert.deepEqual(calls.at(-1), ['42', expected]);
    checks++;
  }
  assert.equal(closes, callbacks.length);
}
console.log(`PASS ${checks} rendered picker callbacks without jQuery, CDN or browser sessions`);

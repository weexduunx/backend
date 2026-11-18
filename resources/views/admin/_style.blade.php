<style>
  :root { --bg:#F6F3EC; --surface:#fff; --ink:#1F2A24; --muted:#5E6863; --line:#E4DED1; --primary:#12664B; --soft:#E3F0EA; --gold:#B08A3E; --danger:#B3261E; --dangerSoft:#FBE4E2; }
  @media (prefers-color-scheme: dark) { :root { --bg:#0F1512; --surface:#18201C; --ink:#EDEFEA; --muted:#A3ACA6; --line:#2A332E; --primary:#5CC29A; --soft:#1C2E26; --gold:#E0BD73; --danger:#F2867D; --dangerSoft:#3A1F1C; } }
  * { box-sizing: border-box; }
  body { margin:0; background:var(--bg); color:var(--ink); font:15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
  .wrap { max-width:1100px; margin:0 auto; padding:24px 16px 64px; }
  h1 { font-size:24px; margin:0; }
  .muted { color:var(--muted); }
  .card { background:var(--surface); border:1px solid var(--line); border-radius:14px; padding:16px; }
  .grid { display:grid; gap:12px; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); }
  .kpi b { display:block; font-size:28px; font-variant-numeric:tabular-nums; }
  .btn { display:inline-block; border:1px solid var(--line); background:var(--surface); color:var(--ink); padding:8px 14px; border-radius:10px; text-decoration:none; font:inherit; cursor:pointer; }
  .btn.primary { background:var(--primary); border-color:var(--primary); color:#fff; }
  input, select { font:inherit; padding:8px 10px; border:1px solid var(--line); border-radius:10px; background:var(--surface); color:var(--ink); max-width:100%; }
  .pill { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; background:var(--soft); color:var(--primary); margin:0 4px 4px 0; }
  .pill.bug { background:var(--dangerSoft); color:var(--danger); }
  .stars { color:var(--gold); letter-spacing:1px; }
  .item { border-top:1px solid var(--line); padding:14px 0; }
  .item:first-child { border-top:0; }
  .msg { white-space:pre-wrap; margin:6px 0; overflow-wrap:anywhere; }
  .meta { font-size:12px; color:var(--muted); }
  .bar { height:8px; background:var(--soft); border-radius:4px; overflow:hidden; }
  .bar span { display:block; height:100%; background:var(--primary); }
  nav.pages { margin-top:16px; }
  nav.pages svg { width:16px; height:16px; }
</style>

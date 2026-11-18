<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Avis bêta · Bayân</title>
  @include('admin._style')
</head>
<body>
  <div class="wrap" style="max-width:420px; padding-top:80px">
    <div class="card">
      <h1>Avis des bêta-testeurs</h1>
      <p class="muted">Bayân · accès réservé</p>
      <form method="post" action="{{ route('admin.feedback.login') }}">
        @csrf
        <label for="password">Mot de passe</label><br>
        <input id="password" type="password" name="password" required autofocus style="width:100%; margin:6px 0 12px">
        @error('password')<p style="color:var(--danger); margin:0 0 12px">{{ $message }}</p>@enderror
        <button class="btn primary" type="submit">Se connecter</button>
      </form>
    </div>
  </div>
</body>
</html>

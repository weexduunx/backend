@php
  $categories = ['general' => 'Avis général', 'bug' => 'Problème', 'idea' => 'Idée', 'content' => 'Contenu'];
  $features = ['lecture' => 'Lecture', 'ecoute' => 'Écoute', 'tafsir' => 'Tafsir', 'prieres' => 'Prières', 'enfants' => 'Espace enfants', 'hors_ligne' => 'Hors ligne'];
  $maxRating = max(1, collect($stats['byRating'])->max() ?? 1);
@endphp
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Avis bêta · Bayân</title>
  @include('admin._style')
</head>
<body>
<div class="wrap">
  <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:20px">
    <div style="flex:1; min-width:200px">
      <h1>Avis des bêta-testeurs</h1>
      <span class="muted">Bayân · {{ $stats['last7'] }} avis ces 7 derniers jours</span>
    </div>
    <a class="btn" href="{{ route('admin.feedback.export', $filters) }}">Exporter en CSV</a>
    <form method="post" action="{{ route('admin.feedback.logout') }}">@csrf<button class="btn" type="submit">Déconnexion</button></form>
  </div>

  <div class="grid">
    <div class="card kpi"><span class="muted">Avis reçus</span><b>{{ $stats['total'] }}</b></div>
    <div class="card kpi"><span class="muted">Testeurs distincts</span><b>{{ $stats['testers'] }}</b></div>
    <div class="card kpi"><span class="muted">Note moyenne</span><b>{{ $stats['average'] ? number_format($stats['average'], 1, ',', ' ') . ' / 5' : '—' }}</b></div>
    <div class="card kpi"><span class="muted">Problèmes signalés</span><b>{{ $stats['byCategory']['bug'] ?? 0 }}</b></div>
  </div>

  <div class="grid" style="margin-top:12px">
    <div class="card">
      <strong>Répartition des notes</strong>
      @for ($r = 5; $r >= 1; $r--)
        @php $n = $stats['byRating'][$r] ?? 0; @endphp
        <div style="display:flex; align-items:center; gap:8px; margin-top:8px">
          <span style="width:26px">{{ $r }}★</span>
          <div class="bar" style="flex:1"><span style="width:{{ round($n / $maxRating * 100) }}%"></span></div>
          <span class="muted" style="width:30px; text-align:right">{{ $n }}</span>
        </div>
      @endfor
    </div>
    <div class="card">
      <strong>Ce que les testeurs apprécient</strong>
      @forelse ($stats['liked'] as $f => $n)
        <div style="display:flex; justify-content:space-between; margin-top:8px"><span>{{ $features[$f] ?? $f }}</span><span class="muted">{{ $n }}</span></div>
      @empty
        <p class="muted">Pas encore de réponse.</p>
      @endforelse
    </div>
  </div>

  <form class="card" method="get" style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap; align-items:center">
    <select name="category">
      <option value="">Toutes les catégories</option>
      @foreach ($categories as $k => $v)<option value="{{ $k }}" @selected(($filters['category'] ?? '') === $k)>{{ $v }}</option>@endforeach
    </select>
    <select name="rating">
      <option value="">Toutes les notes</option>
      @for ($r = 5; $r >= 1; $r--)<option value="{{ $r }}" @selected((int) ($filters['rating'] ?? 0) === $r)>{{ $r }} étoile{{ $r > 1 ? 's' : '' }}</option>@endfor
    </select>
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Rechercher dans les messages" style="flex:1; min-width:180px">
    <button class="btn primary" type="submit">Filtrer</button>
  </form>

  <div class="card" style="margin-top:12px">
    @forelse ($items as $f)
      <div class="item">
        <div>
          <span class="pill {{ $f->category === 'bug' ? 'bug' : '' }}">{{ $categories[$f->category] ?? $f->category }}</span>
          @if ($f->rating)<span class="stars">{{ str_repeat('★', $f->rating) }}{{ str_repeat('☆', 5 - $f->rating) }}</span>@endif
          @foreach ((array) $f->liked as $l)<span class="pill">{{ $features[$l] ?? $l }}</span>@endforeach
        </div>
        @if ($f->message)<p class="msg">{{ $f->message }}</p>@endif
        <div class="meta">
          {{ $f->created_at->timezone('Africa/Dakar')->format('d/m/Y H:i') }}
          · {{ $f->platform }} {{ $f->os_version }} · {{ $f->device }} · v{{ $f->app_version }}{{ $f->build ? ' (' . $f->build . ')' : '' }}
          @if ($f->place) · {{ $f->place }}@endif
          @if ($f->screen) · écran : {{ $f->screen }}@endif
          @if ($f->contact) · <strong>contact : {{ $f->contact }}</strong>@endif
        </div>
      </div>
    @empty
      <p class="muted">Aucun avis pour ces filtres.</p>
    @endforelse
    <nav class="pages">{{ $items->links() }}</nav>
  </div>
</div>
</body>
</html>

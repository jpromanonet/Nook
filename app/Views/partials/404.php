<div class="empty-page">
    <h1>This page isn’t here.</h1>
    <p class="muted">Nothing urgent. You can go back home.</p>
    <p><a class="btn btn-primary" href="<?= e(url(Auth::check() ? '/home' : '/')) ?>">Home</a></p>
</div>

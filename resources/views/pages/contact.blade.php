<x-landing.layout :seo="$seo">
<div class="max-w-6xl mx-auto px-5">
  <div class="pt-12 pb-10 border-b border-ink">
    <p class="font-mono text-xs uppercase tracking-wider text-mute"><a class="hover:underline" href="/">Nanybot</a> / Contact</p>
    <h1 class="mt-5 text-4xl sm:text-6xl font-extrabold tracking-tight">Contact</h1>
    <p class="mt-5 max-w-2xl text-lg text-mute">Email is the fastest way to reach a person. We reply within 2 working days.</p>
  </div>

  <div class="mt-12 grid gap-14 lg:grid-cols-2">
    <section aria-labelledby="who">
      <h2 id="who" class="font-mono text-xs uppercase tracking-wider text-mute">Pick the right inbox</h2>
      <dl class="mt-4 border-t border-ink">
        <div class="border-b border-ink py-5">
          <dt class="font-bold text-lg">Product help</dt>
          <dd class="mt-1 text-mute">Something is not working, or you need a hand setting up.</dd>
          <dd class="mt-2 font-mono text-sm"><a class="underline underline-offset-4 hover:text-rust" href="mailto:support@nanybot.com">support@nanybot.com</a></dd>
        </div>
        <div class="border-b border-ink py-5">
          <dt class="font-bold text-lg">Billing and refunds</dt>
          <dd class="mt-1 text-mute">Charges, receipts and <a class="underline underline-offset-4" href="{{ route('refund') }}">refund requests</a>.</dd>
          <dd class="mt-2 font-mono text-sm"><a class="underline underline-offset-4 hover:text-rust" href="mailto:billing@nanybot.com">billing@nanybot.com</a></dd>
        </div>
        <div class="border-b border-ink py-5">
          <dt class="font-bold text-lg">Privacy and data</dt>
          <dd class="mt-1 text-mute">Copies, corrections and deletion under the <a class="underline underline-offset-4" href="{{ route('privacy') }}">privacy policy</a>.</dd>
          <dd class="mt-2 font-mono text-sm"><a class="underline underline-offset-4 hover:text-rust" href="mailto:privacy@nanybot.com">privacy@nanybot.com</a></dd>
        </div>
      </dl>
      <p class="mt-6 text-sm text-mute">Helpful to include: the product, your device or browser, and what you expected to happen.</p>
    </section>

    <section aria-labelledby="write">
      <h2 id="write" class="font-mono text-xs uppercase tracking-wider text-mute">Or write it here</h2>
      <form id="contact-form" class="mt-4 space-y-5" novalidate>
        <div>
          <label for="topic" class="block text-sm font-semibold">What is it about?</label>
          <select id="topic" class="field">
            <option value="support@nanybot.com">Product help</option>
            <option value="billing@nanybot.com">Billing or refund</option>
            <option value="privacy@nanybot.com">Privacy or data</option>
            <option value="support@nanybot.com" data-idea>A task you want automated</option>
          </select>
        </div>
        <div>
          <label for="name" class="block text-sm font-semibold">Your name</label>
          <input id="name" type="text" autocomplete="name" class="field">
        </div>
        <div>
          <label for="message" class="block text-sm font-semibold">Message</label>
          <textarea id="message" rows="6" class="field" required></textarea>
        </div>
        <button type="submit" class="bg-ink text-paper px-6 py-3.5 font-semibold hover:bg-rust transition-colors">Open in my email app</button>
        <p class="text-xs text-mute">This opens a ready-to-send message in your own email app. Nothing is sent from this page.</p>
      </form>
    </section>
  </div>
</div>
@push('scripts')
<script>
(function () {
  // Highlight the current section in the legal-page table of contents.
  var links = document.querySelectorAll('.toc a');
  if (links.length && 'IntersectionObserver' in window) {
    var byId = {};
    links.forEach(function (a) { byId[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          links.forEach(function (a) { a.classList.remove('is-active'); });
          byId[e.target.id].classList.add('is-active');
        }
      });
    }, { rootMargin: '-10% 0px -75% 0px' });
    document.querySelectorAll('.legal section[id]').forEach(function (s) { io.observe(s); });
  }

  // Contact form: build a mailto: link, nothing is sent from the page.
  var form = document.getElementById('contact-form');
  if (form) {
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      var sel = form.querySelector('#topic');
      var label = sel.options[sel.selectedIndex].text;
      var name = form.querySelector('#name').value.trim();
      var msg = form.querySelector('#message');
      if (!msg.value.trim()) { msg.focus(); return; }
      var body = msg.value.trim() + (name ? '\n\n' + name : '');
      location.href = 'mailto:' + sel.value +
        '?subject=' + encodeURIComponent('[Nanybot] ' + label) +
        '&body=' + encodeURIComponent(body);
    });
  }
})();
</script>
@endpush
</x-landing.layout>

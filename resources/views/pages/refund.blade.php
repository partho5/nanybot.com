@php($email = config('services.contact_email'))
<x-landing.layout :seo="$seo">
<div class="max-w-6xl mx-auto px-5">
  <div class="pt-12 pb-10 border-b border-ink">
    <p class="font-mono text-xs uppercase tracking-wider text-mute"><a class="hover:underline" href="/">Nanybot</a> / Legal / Refund policy</p>
    <h1 class="mt-5 text-4xl sm:text-6xl font-extrabold tracking-tight">Refund policy</h1>
    <p class="mt-5 max-w-2xl text-lg text-mute">If a paid plan is not right for you, you have 30 days to get your money back.</p>
    <p class="mt-5 font-mono text-xs uppercase tracking-wider">Last updated <time datetime="2026-10-14">14 October 2026</time></p>
  </div>
  <div class="mt-10 border border-ink p-6 sm:p-8 max-w-3xl">
    <p class="font-mono text-xs uppercase tracking-wider text-rust">In short</p>
    <ul class="mt-3 space-y-2 glance"><li>30 days from your first payment, no hoops.</li><li>Free trials need no card, so there is nothing to refund.</li><li>Money goes back to the payment method you used.</li><li>We reply to refund requests within 2 working days.</li></ul>
  </div>
  <div class="mt-14 grid gap-12 lg:grid-cols-[14rem_1fr]">
    <aside class="lg:sticky lg:top-6 lg:self-start">
      <p class="font-mono text-xs uppercase tracking-wider text-mute">On this page</p>
      <ol class="toc mt-3 text-sm"><li><a href="#window">01 &nbsp;The 30-day window</a></li><li><a href="#covered">02 &nbsp;What is covered</a></li><li><a href="#notcovered">03 &nbsp;What is not covered</a></li><li><a href="#how">04 &nbsp;How to ask</a></li><li><a href="#money">05 &nbsp;When the money comes back</a></li><li><a href="#after">06 &nbsp;After a refund</a></li><li><a href="#contact">07 &nbsp;Payment questions</a></li></ol>
    </aside>
    <article class="legal max-w-3xl"><section id="window" class="scroll-mt-6"><h2>01 &nbsp;The 30-day window</h2><ol class="clauses"><li>Ask within 30 days of your first payment for a paid plan and we will refund it in full.</li><li>The window starts on the date of the first payment and does not restart on later purchases of the same plan.</li></ol></section><section id="covered" class="scroll-mt-6"><h2>02 &nbsp;What is covered</h2><div class="overflow-x-auto"><table class="refund-table">
<thead><tr><th>Product</th><th>Plan</th><th>Refund window</th></tr></thead>
<tbody>
<tr><td>Laptop Care</td><td>Pro Annual, Pro Lifetime</td><td>30 days from first payment</td></tr>
<tr><td>Nany Social</td><td>Any paid plan</td><td>30 days from first payment</td></tr>
</tbody></table></div></section><section id="notcovered" class="scroll-mt-6"><h2>03 &nbsp;What is not covered</h2><ol class="clauses"><li>Payments older than 30 days, unless the law where you live says otherwise.</li><li>AI credits you have already used. Credits you used are deducted from any refund. Unused credits from a pack bought in the last 30 days can be refunded.</li><li>Free plans and free trials, which cost nothing.</li></ol></section><section id="how" class="scroll-mt-6"><h2>04 &nbsp;How to ask</h2><ol class="clauses"><li>Email <a href='mailto:{{ $email }}'>{{ $email }}</a> from your account email.</li><li>Include the product name and your payment or transaction ID. A reason is welcome but not required.</li><li>We reply within 2 working days.</li></ol></section><section id="money" class="scroll-mt-6"><h2>05 &nbsp;When the money comes back</h2><ol class="clauses"><li>Refunds go to the original payment method, for example the PayPal account you paid with.</li><li>Your payment provider usually posts it within 5 to 10 working days.</li></ol></section><section id="after" class="scroll-mt-6"><h2>06 &nbsp;After a refund</h2><ol class="clauses"><li>Paid features end and lock on your devices. Free features keep working.</li><li>Your files, settings and backups are not touched.</li></ol></section><section id="contact" class="scroll-mt-6"><h2>07 &nbsp;Payment questions</h2><ol class="clauses"><li>Anything unclear about a charge: <a href='mailto:{{ $email }}'>{{ $email }}</a>. See also the <a href='/terms/'>terms of service</a>.</li></ol></section>
      <div class="mt-16 pt-6 border-t border-ink text-sm flex flex-wrap gap-x-6 gap-y-2"><span class="font-mono text-xs uppercase tracking-wider text-mute">Also read</span><a class="underline underline-offset-4 hover:text-rust" href="{{ route('privacy') }}">Privacy policy</a><a class="underline underline-offset-4 hover:text-rust" href="{{ route('terms') }}">Terms of service</a><a class="underline underline-offset-4 hover:text-rust" href="{{ route('contact') }}">Contact</a></div>
    </article>
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

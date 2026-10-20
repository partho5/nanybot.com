<x-landing.layout :seo="$seo">
<div class="max-w-6xl mx-auto px-5">
  <div class="pt-12 pb-10 border-b border-ink">
    <p class="font-mono text-xs uppercase tracking-wider text-mute"><a class="hover:underline" href="/">Nanybot</a> / Legal / Privacy policy</p>
    <h1 class="mt-5 text-4xl sm:text-6xl font-extrabold tracking-tight">Privacy policy</h1>
    <p class="mt-5 max-w-2xl text-lg text-mute">What the Nanybot website collects, why, and what you can ask us to do about it.</p>
    <p class="mt-5 font-mono text-xs uppercase tracking-wider">Last updated <time datetime="2026-10-14">14 October 2026</time></p>
  </div>
  <div class="mt-10 border border-ink p-6 sm:p-8 max-w-3xl">
    <p class="font-mono text-xs uppercase tracking-wider text-rust">In short</p>
    <ul class="mt-3 space-y-2 glance"><li>This website has no ads, no ad trackers and no third-party analytics.</li><li>We never sell personal data.</li><li>Each software product has its own privacy policy for the data it handles.</li><li>You can ask for a copy or deletion of your data at any time.</li></ul>
  </div>
  <div class="mt-14 grid gap-12 lg:grid-cols-[14rem_1fr]">
    <aside class="lg:sticky lg:top-6 lg:self-start">
      <p class="font-mono text-xs uppercase tracking-wider text-mute">On this page</p>
      <ol class="toc mt-3 text-sm"><li><a href="#who">01 &nbsp;Who we are</a></li><li><a href="#scope">02 &nbsp;What this policy covers</a></li><li><a href="#collect">03 &nbsp;What we collect</a></li><li><a href="#use">04 &nbsp;Why we use it</a></li><li><a href="#share">05 &nbsp;Who we share it with</a></li><li><a href="#cookies">06 &nbsp;Cookies and fonts</a></li><li><a href="#keep">07 &nbsp;How long we keep it</a></li><li><a href="#rights">08 &nbsp;Your choices</a></li><li><a href="#safety">09 &nbsp;Security and children</a></li><li><a href="#changes">10 &nbsp;Changes to this policy</a></li></ol>
    </aside>
    <article class="legal max-w-3xl"><section id="who" class="scroll-mt-6"><h2>01 &nbsp;Who we are</h2><ol class="clauses"><li>Nanybot is a software service operated by Protim Soft (&ldquo;we&rdquo;, &ldquo;us&rdquo;).</li><li>For privacy questions write to <a href='mailto:privacy@nanybot.com'>privacy@nanybot.com</a>.</li></ol></section><section id="scope" class="scroll-mt-6"><h2>02 &nbsp;What this policy covers</h2><ol class="clauses"><li>This policy covers nanybot.com and the messages you send us by email or through the contact page.</li><li>It does not cover data handled inside our software. Laptop Care and Nany Social each publish a policy that describes what the product itself collects. Read that one before you install or sign up.</li></ol></section><section id="collect" class="scroll-mt-6"><h2>03 &nbsp;What we collect</h2><ol class="clauses"><li><strong>Messages you send.</strong> Your email address, name if you give it, and whatever you write to us.</li><li><strong>Server logs.</strong> When you load a page, our hosting provider records your IP address, browser type, the page requested and the time. This is standard for any website.</li><li><strong>Nothing else.</strong> The contact page does not send data from your browser to us. It opens your own email app with a message prepared for you to send.</li></ol></section><section id="use" class="scroll-mt-6"><h2>04 &nbsp;Why we use it</h2><ol class="clauses"><li>To answer your message and help with your problem.</li><li>To keep the website secure and stop abuse, using the server logs.</li><li>To meet legal obligations, such as tax or accounting records for paid purchases.</li></ol></section><section id="share" class="scroll-mt-6"><h2>05 &nbsp;Who we share it with</h2><ol class="clauses"><li>Our hosting provider and email provider process data on our behalf so the site and our inbox work.</li><li>We disclose data to authorities only where the law requires it.</li><li>We do not sell personal data or share it for advertising.</li></ol></section><section id="cookies" class="scroll-mt-6"><h2>06 &nbsp;Cookies and fonts</h2><ol class="clauses"><li>This site sets no cookies of its own.</li><li>The pages load fonts from Google Fonts. When they do, Google receives your IP address and browser details in the font request.</li></ol></section><section id="keep" class="scroll-mt-6"><h2>07 &nbsp;How long we keep it</h2><ol class="clauses"><li>Support emails are kept while we help you and for a reasonable period afterwards in case you write back.</li><li>Server logs are kept for a short rolling period.</li><li>Payment and tax records are kept for as long as the law requires.</li></ol></section><section id="rights" class="scroll-mt-6"><h2>08 &nbsp;Your choices</h2><ol class="clauses"><li>You may ask us for a copy of the personal data we hold about you, to correct it, or to delete it.</li><li>Email <a href='mailto:privacy@nanybot.com'>privacy@nanybot.com</a> from the address you used with us. We aim to reply within 30 days.</li></ol></section><section id="safety" class="scroll-mt-6"><h2>09 &nbsp;Security and children</h2><ol class="clauses"><li>The site is served over HTTPS and access to our inbox is limited to the people who need it.</li><li>Our services are for business use and are not directed at children under 13.</li></ol></section><section id="changes" class="scroll-mt-6"><h2>10 &nbsp;Changes to this policy</h2><ol class="clauses"><li>If we change this policy, the date at the top changes. For material changes we will also say so on the home page.</li></ol></section>
      <div class="mt-16 pt-6 border-t border-ink text-sm flex flex-wrap gap-x-6 gap-y-2"><span class="font-mono text-xs uppercase tracking-wider text-mute">Also read</span><a class="underline underline-offset-4 hover:text-rust" href="{{ route('terms') }}">Terms of service</a><a class="underline underline-offset-4 hover:text-rust" href="{{ route('refund') }}">Refund policy</a><a class="underline underline-offset-4 hover:text-rust" href="{{ route('contact') }}">Contact</a></div>
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

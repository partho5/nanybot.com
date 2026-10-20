@php($email = config('services.contact_email'))
<x-landing.layout :seo="$seo">
<div class="max-w-6xl mx-auto px-5">
  <div class="pt-12 pb-10 border-b border-ink">
    <p class="font-mono text-xs uppercase tracking-wider text-mute"><a class="hover:underline" href="/">Nanybot</a> / Legal / Terms of service</p>
    <h1 class="mt-5 text-4xl sm:text-6xl font-extrabold tracking-tight">Terms of service</h1>
    <p class="mt-5 max-w-2xl text-lg text-mute">The rules for using Nanybot's website and software. Short and plain on purpose.</p>
    <p class="mt-5 font-mono text-xs uppercase tracking-wider">Last updated <time datetime="2026-10-14">14 October 2026</time></p>
  </div>
  <div class="mt-10 border border-ink p-6 sm:p-8 max-w-3xl">
    <p class="font-mono text-xs uppercase tracking-wider text-rust">In short</p>
    <ul class="mt-3 space-y-2 glance"><li>Use Nanybot for lawful business work and keep your account details accurate.</li><li>Automation acts on your behalf, so review what it does and keep your own backups.</li><li>Paid plans are refundable under the refund policy.</li><li>We provide the service as is and our liability is capped.</li></ul>
  </div>
  <div class="mt-14 grid gap-12 lg:grid-cols-[14rem_1fr]">
    <aside class="lg:sticky lg:top-6 lg:self-start">
      <p class="font-mono text-xs uppercase tracking-wider text-mute">On this page</p>
      <ol class="toc mt-3 text-sm"><li><a href="#agree">01 &nbsp;Agreement</a></li><li><a href="#service">02 &nbsp;The service</a></li><li><a href="#eligibility">03 &nbsp;Who can use it</a></li><li><a href="#use">04 &nbsp;Acceptable use</a></li><li><a href="#automation">05 &nbsp;Automation is your responsibility</a></li><li><a href="#payments">06 &nbsp;Plans and payments</a></li><li><a href="#ip">07 &nbsp;Ownership</a></li><li><a href="#third">08 &nbsp;Third-party services</a></li><li><a href="#changes">09 &nbsp;Changes to the service</a></li><li><a href="#liability">10 &nbsp;Warranty and liability</a></li><li><a href="#ending">11 &nbsp;Ending your use</a></li><li><a href="#contact">12 &nbsp;Contact</a></li></ol>
    </aside>
    <article class="legal max-w-3xl"><section id="agree" class="scroll-mt-6"><h2>01 &nbsp;Agreement</h2><ol class="clauses"><li>By using nanybot.com or any Nanybot software you agree to these terms. If you do not agree, do not use the service.</li><li>Each product may add its own terms, for example licence details inside the app. Where they conflict with these terms for that product, the product terms win.</li></ol></section><section id="service" class="scroll-mt-6"><h2>02 &nbsp;The service</h2><ol class="clauses"><li>Nanybot provides software that automates repeated business tasks. Today that includes Laptop Care and Nany Social.</li><li>Features, limits and pricing are shown on each product page or inside the product.</li></ol></section><section id="eligibility" class="scroll-mt-6"><h2>03 &nbsp;Who can use it</h2><ol class="clauses"><li>You must be at least 18 and able to enter a contract, or be acting for a business that is.</li><li>Give accurate details when you sign up, and keep your sign-in secure. You are responsible for activity under your account.</li></ol></section><section id="use" class="scroll-mt-6"><h2>04 &nbsp;Acceptable use</h2><ol class="clauses"><li>Do not attack, probe or overload the service, or bypass its limits.</li><li>Do not use it for anything unlawful, deceptive or abusive, including spam or content you have no right to publish.</li><li>Do not resell or sublicense access without our written permission.</li><li>We may limit or suspend access when these rules are broken.</li></ol></section><section id="automation" class="scroll-mt-6"><h2>05 &nbsp;Automation is your responsibility</h2><ol class="clauses"><li>Our software acts on your instructions, on your devices or accounts. You decide what runs and when.</li><li>Review what it does, check any AI-generated suggestions before relying on them, and keep your own backups.</li><li>Where the software connects to third-party platforms, such as social networks, you must also follow those platforms' rules.</li></ol></section><section id="payments" class="scroll-mt-6"><h2>06 &nbsp;Plans and payments</h2><ol class="clauses"><li>Prices, plan length and what each plan includes are shown at the point of purchase.</li><li>Payments go through our payment providers. We do not see or store your full card details.</li><li>Refunds are handled under the <a href='/refund/'>refund policy</a>.</li></ol></section><section id="ip" class="scroll-mt-6"><h2>07 &nbsp;Ownership</h2><ol class="clauses"><li>We own the software and the Nanybot name. You get a limited, non-exclusive right to use the software while you follow these terms.</li><li>You keep ownership of your own files, accounts and content.</li></ol></section><section id="third" class="scroll-mt-6"><h2>08 &nbsp;Third-party services</h2><ol class="clauses"><li>Some features rely on providers we do not control, such as AI models, payment processors and social platforms. Their availability and terms are outside our control.</li></ol></section><section id="changes" class="scroll-mt-6"><h2>09 &nbsp;Changes to the service</h2><ol class="clauses"><li>We may improve, change or retire features and may update these terms. Continued use after a change means you accept it.</li><li>Software updates may be delivered automatically, for example through an app store.</li></ol></section><section id="liability" class="scroll-mt-6"><h2>10 &nbsp;Warranty and liability</h2><ol class="clauses"><li>The service is provided &ldquo;as is&rdquo;. We do not promise it will be uninterrupted or error free, or that it will deliver a particular saving or result.</li><li>To the extent the law allows, our total liability to you is limited to what you paid us in the 12 months before the claim.</li><li>Nothing here removes rights you have that cannot be removed by law.</li></ol></section><section id="ending" class="scroll-mt-6"><h2>11 &nbsp;Ending your use</h2><ol class="clauses"><li>You can stop at any time by uninstalling or closing your account.</li><li>We may end or restrict access if you break these terms. Data handling after that follows the <a href='/privacy/'>privacy policy</a>.</li></ol></section><section id="contact" class="scroll-mt-6"><h2>12 &nbsp;Contact</h2><ol class="clauses"><li>Questions about these terms: <a href='mailto:{{ $email }}'>{{ $email }}</a>.</li></ol></section>
      <div class="mt-16 pt-6 border-t border-ink text-sm flex flex-wrap gap-x-6 gap-y-2"><span class="font-mono text-xs uppercase tracking-wider text-mute">Also read</span><a class="underline underline-offset-4 hover:text-rust" href="{{ route('privacy') }}">Privacy policy</a><a class="underline underline-offset-4 hover:text-rust" href="{{ route('refund') }}">Refund policy</a><a class="underline underline-offset-4 hover:text-rust" href="{{ route('contact') }}">Contact</a></div>
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

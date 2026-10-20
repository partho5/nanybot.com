<x-landing.layout :seo="$seo">
  <section class="border-b border-ink">
    <div class="max-w-6xl mx-auto px-5 pt-16 pb-16 sm:pt-24 sm:pb-20 grid gap-12 lg:grid-cols-12 lg:items-end">
      <div class="lg:col-span-8">
        <p class="font-mono text-xs uppercase tracking-wider text-mute">Workflow software for small business</p>
        <h1 class="mt-6 font-extrabold tracking-tight leading-[0.92] text-[clamp(2.9rem,7vw,5.5rem)]">
          Repeated tasks <span class="block text-rust">automated.</span>
        </h1>
        <p class="mt-8 text-2xl sm:text-3xl font-semibold tracking-tight">For business owners.</p>
        <div class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-4">
          <a href="#software" class="inline-block bg-ink text-paper px-6 py-3.5 font-semibold hover:bg-rust transition-colors">See the software</a>
          <a href="{{ route('contact') }}" class="font-semibold underline underline-offset-4 decoration-2 hover:text-rust">Tell us what you repeat</a>
        </div>
      </div>
      <aside class="lg:col-span-4 border border-ink p-6" aria-label="Cost saving">
        <p class="font-mono text-xs uppercase tracking-wider text-mute">Cut cost by</p>
        <p class="mt-2 font-mono font-medium tracking-tighter text-6xl sm:text-7xl">70&ndash;80<span class="text-rust">%</span></p>
        <p class="mt-5 border-t border-ink pt-4 text-xs leading-relaxed text-mute">Typical saving against doing the same work by hand or with paid staff. Your result depends on the workflow.</p>
      </aside>
    </div>
  </section>

  <section id="software" class="scroll-mt-4 max-w-6xl mx-auto px-5 pt-16">
    <div class="flex items-baseline justify-between gap-4 pb-5">
      <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Software</h2>
      <p class="font-mono text-xs uppercase tracking-wider text-mute">02 products, more coming</p>
    </div>
    <div class="grid md:grid-cols-2 gap-px bg-ink border border-ink">
      <a href="/ai-laptop-solver/" class="group flex flex-col justify-between gap-12 bg-paper p-6 sm:p-8 min-h-[24rem] transition-colors duration-150 hover:bg-ink hover:text-paper">
        <div>
          <div class="flex items-start justify-between gap-4 font-mono text-xs uppercase tracking-wider">
            <span>01</span>
            <span class="text-mute group-hover:text-paper/70 text-right">Windows 10/11 &middot; Free + Pro</span>
          </div>
          <h3 class="mt-12 text-4xl sm:text-5xl font-extrabold tracking-tight">Laptop Care</h3>
          <p class="mt-2 font-mono text-sm text-mute group-hover:text-paper/70">AI Laptop Solver</p>
          <p class="mt-6 max-w-md text-lg leading-snug">Scans a slow Windows PC, finds what is dragging it down and fixes it, with every change shown first and undoable.</p>
        </div>
        <div>
          <p class="text-sm"><span class="font-mono uppercase tracking-wider text-xs text-mute group-hover:text-paper/70">Replaces&nbsp;&nbsp;</span>A technician visit to find out why the office PC crawls.</p>
          <span class="mt-5 flex items-center justify-between border-t border-current pt-4 font-semibold">Open Laptop Care <svg class="w-5 h-5 shrink-0 transition-transform duration-150 group-hover:translate-x-1.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10h13M11 4l6 6-6 6"/></svg></span>
        </div>
      </a>

      <a href="https://social.nanybot.com/" class="group flex flex-col justify-between gap-12 bg-paper p-6 sm:p-8 min-h-[24rem] transition-colors duration-150 hover:bg-ink hover:text-paper">
        <div>
          <div class="flex items-start justify-between gap-4 font-mono text-xs uppercase tracking-wider">
            <span>02</span>
            <span class="text-mute group-hover:text-paper/70 text-right">30+ platforms &middot; Scheduling</span>
          </div>
          <h3 class="mt-12 text-4xl sm:text-5xl font-extrabold tracking-tight">Nany Social</h3>
          <p class="mt-2 font-mono text-sm text-mute group-hover:text-paper/70">Social media autopilot</p>
          <p class="mt-6 max-w-md text-lg leading-snug">A social media autopilot with scheduling. Queue your posts once and they publish across 30+ platforms on time.</p>
        </div>
        <div>
          <p class="text-sm"><span class="font-mono uppercase tracking-wider text-xs text-mute group-hover:text-paper/70">Replaces&nbsp;&nbsp;</span>Posting by hand, account by account, every day.</p>
          <span class="mt-5 flex items-center justify-between border-t border-current pt-4 font-semibold">Open Nany Social <svg class="w-5 h-5 shrink-0 transition-transform duration-150 group-hover:translate-x-1.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10h13M11 4l6 6-6 6"/></svg></span>
        </div>
      </a>

    </div>
  </section>

  <section id="how" class="scroll-mt-4 max-w-6xl mx-auto px-5 pt-24">
    <div class="grid gap-10 lg:grid-cols-12">
      <h2 class="lg:col-span-4 text-2xl sm:text-3xl font-extrabold tracking-tight">Where the 70&ndash;80% comes from</h2>
      <ol class="lg:col-span-8 border-t border-ink">
        <li class="grid grid-cols-[3.5rem_1fr] sm:grid-cols-[5rem_1fr] gap-4 border-b border-ink py-6">
          <span class="font-mono text-sm text-rust">01</span>
          <div><h3 class="text-xl font-bold tracking-tight">We pick work that repeats</h3>
          <p class="mt-2 max-w-xl text-mute">Same steps, same rules, every day or week. If a person does it from a checklist, software can do it.</p></div>
        </li>
        <li class="grid grid-cols-[3.5rem_1fr] sm:grid-cols-[5rem_1fr] gap-4 border-b border-ink py-6">
          <span class="font-mono text-sm text-rust">02</span>
          <div><h3 class="text-xl font-bold tracking-tight">Software does it on a schedule</h3>
          <p class="mt-2 max-w-xl text-mute">No hiring, no hand-offs, no waiting for someone to get to it. The task runs when it should, at the same quality every time.</p></div>
        </li>
        <li class="grid grid-cols-[3.5rem_1fr] sm:grid-cols-[5rem_1fr] gap-4 border-b border-ink py-6">
          <span class="font-mono text-sm text-rust">03</span>
          <div><h3 class="text-xl font-bold tracking-tight">You review exceptions, not every task</h3>
          <p class="mt-2 max-w-xl text-mute">You stay in control and approve what matters. The hours you were spending go back to the business.</p></div>
        </li>
      </ol>
    </div>
  </section>

  <section class="max-w-6xl mx-auto px-5 pt-24">
    <div class="bg-ink text-paper p-8 sm:p-12 grid gap-8 md:grid-cols-[1fr_auto] md:items-end">
      <div>
        <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Doing the same thing every week?</h2>
        <p class="mt-3 max-w-lg text-paper/70">Tell us what it is. If it repeats, it is probably the next Nanybot.</p>
      </div>
      <a href="{{ route('contact') }}" class="inline-block bg-paper text-ink px-6 py-3.5 font-semibold hover:bg-rust hover:text-paper transition-colors">Get in touch</a>
    </div>
  </section>
</x-landing.layout>

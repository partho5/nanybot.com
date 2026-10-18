<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Services\Blog\ArticleWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Local/staging sample content. Safe to re-run: users, categories, tags and
 * articles are matched by email/slug and skipped when they already exist.
 *
 * Admin credentials come from SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD and
 * default to admin@nanybot.test / password. Authors use password "password".
 * Articles are written through ArticleWriter, so body_html, TOC, FAQ, excerpt
 * and read time are computed exactly as in production. Images are
 * Cloudinary-style placeholder URLs with explicit width/height, so no network
 * request is made.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $writer = app(ArticleWriter::class);

        if (app()->isProduction()) {
            return;
        }

        $admin = $this->user(
            env('SEED_ADMIN_EMAIL', 'admin@nanybot.test'),
            'NanyBot Admin',
            'nanybot-admin',
            UserRole::Admin,
            'Editor in chief',
            'Runs the NanyBot blog and keeps the lights on.',
            env('SEED_ADMIN_PASSWORD', 'password'),
        );
        $authors = [
            $this->user('maya@nanybot.test', 'Maya Okafor', 'maya-okafor', UserRole::Author, 'Backend engineer', 'Maya builds queues, webhooks and the occasional chatbot. She writes about making Laravel apps fast and boring in the best way.'),
            $this->user('liam@nanybot.test', 'Liam Chen', 'liam-chen', UserRole::Author, 'Conversation designer', 'Liam designs bot conversations people actually finish. Previously support lead, now full-time bot whisperer.'),
        ];
        $writers = [$admin, ...$authors];

        $categories = collect([
            ['Tutorials', 'Step-by-step walkthroughs you can follow in an afternoon.'],
            ['Engineering', 'How NanyBot is built, and what we learned building it.'],
            ['Product Updates', 'New features, improvements and the occasional fix.'],
            ['Guides', 'Longer reads on bots, automation and support workflows.'],
        ])->mapWithKeys(fn ($c) => [Str::slug($c[0]) => Category::firstOrCreate(
            ['slug' => Str::slug($c[0])],
            ['name' => $c[0], 'description' => $c[1]],
        )]);

        foreach (['Laravel', 'Chatbots', 'Automation', 'AI', 'PHP', 'Performance', 'SEO', 'Webhooks'] as $name) {
            Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ($this->articles() as $i => $a) {
            $slug = Str::slug($a['title']);
            if (Article::where('slug', $slug)->exists()) {
                continue;
            }

            $status = $a['status'] ?? 'published';

            $writer->save(new Article, [
                'author_id' => $writers[$i % count($writers)]->id,
                'category_id' => $categories[$a['category']]->id,
                'title' => $a['title'],
                'slug' => $slug,
                'excerpt' => $a['excerpt'] ?? null,
                'meta_description' => $a['meta_description'] ?? null,
                'body' => $a['body'],
                'featured_image' => $a['image'] ?? null,
                'featured_image_alt' => isset($a['image']) ? $a['title'] : null,
                'featured_image_caption' => $a['caption'] ?? null,
                'status' => $status,
                'publish_at' => match ($status) {
                    'draft' => null,
                    'scheduled' => Carbon::now()->addDays($a['in_days'] ?? 5)->setTime(9, 0),
                    default => Carbon::now()->subDays($a['days_ago'])->setTime(8 + $i % 8, 30),
                },
                'tags' => $a['tags'],
            ]);
        }
    }

    private function user(string $email, string $name, string $username, UserRole $role, string $headline, string $bio, string $password = 'password'): User
    {
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->forceFill([
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'headline' => $headline,
                'bio' => $bio,
                'password' => $password,
                'email_verified_at' => now(),
                'social_links' => ['github' => 'https://github.com/'.$username],
            ])->save();
        }

        return $user;
    }

    /** Cloudinary-style placeholder (the processor builds srcset from it; no network). */
    private function img(string $name): string
    {
        return "https://res.cloudinary.com/demo/image/upload/v1/blog/{$name}.jpg";
    }

    private function figure(string $name, string $alt, string $caption): string
    {
        return '<p><img src="'.$this->img($name).'" alt="'.$alt.'" width="1600" height="900" data-caption="'.$caption.'"></p>';
    }

    private function code(string $lang, string $source): string
    {
        return '<pre><code class="language-'.$lang.'">'.e($source).'</code></pre>';
    }

    /** @param  array<int, string>  $items */
    private function list(array $items, bool $ordered = false): string
    {
        $tag = $ordered ? 'ol' : 'ul';

        return "<{$tag}>".collect($items)->map(fn ($i) => "<li><p>{$i}</p></li>")->implode('')."</{$tag}>";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function articles(): array
    {
        $webhookCode = $this->code('bash', "curl -X POST https://nanybot.com/api/v1/articles \\\n  -H \"Authorization: Bearer nb_your_key\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"title\": \"Hello\", \"body\": \"<p>First post</p>\", \"status\": \"draft\"}'");
        $queueCode = $this->code('php', "class SendTranscript implements ShouldQueue\n{\n    use Queueable;\n\n    public function __construct(public Conversation \$conversation) {}\n\n    public function handle(Mailer \$mailer): void\n    {\n        \$mailer->to(\$this->conversation->email)->send(new Transcript(\$this->conversation));\n    }\n}");
        $cacheCode = $this->code('php', "Route::middleware('public')->group(function () {\n    Route::get('/blog', [BlogController::class, 'index']);\n    Route::get('/blog/{slug}', [BlogController::class, 'show']);\n});");
        $intentCode = $this->code('python', "def route(message: str) -> str:\n    text = message.lower()\n    if any(w in text for w in (\"refund\", \"charge\")):\n        return \"billing\"\n    if \"password\" in text:\n        return \"account\"\n    return \"general\"");
        $jsonCode = $this->code('json', "{\n  \"intent\": \"billing\",\n  \"confidence\": 0.93,\n  \"handoff\": false\n}");

        return [
            [
                'title' => 'Getting Started With NanyBot in Ten Minutes',
                'category' => 'tutorials', 'tags' => ['Chatbots', 'Automation'], 'days_ago' => 120,
                'image' => $this->img('getting-started'), 'caption' => 'The NanyBot dashboard right after sign-up',
                'body' => '<p>Setting up your first bot should not need a weekend. This walkthrough takes you from an empty account to a bot answering real questions on your site in about ten minutes.</p>'
                    .'<h2>Create your first bot</h2><p>Pick a name, choose a tone and paste in the URL of your help center. NanyBot reads the pages and builds a first set of answers you can review before anything goes live.</p>'
                    .$this->figure('dashboard', 'NanyBot dashboard with a new bot', 'Your new bot, ready for review')
                    .'<h2>Teach it what matters</h2><p>Good bots are narrow. Start with the five questions your team answers most often, then expand as you see real conversations.</p>'
                    .$this->list(['Add your top five questions and answers.', 'Set a fallback message that offers a human.', 'Mark topics the bot must never answer, such as legal advice.'])
                    .'<h2>Put it on your site</h2><p>Copy the snippet from the install tab and paste it before the closing body tag. The widget loads asynchronously, so it will not slow your pages down.</p>',
            ],
            [
                'title' => 'How We Cut Our Blog Page Weight to Zero JavaScript',
                'category' => 'engineering', 'tags' => ['Performance', 'Laravel', 'SEO'], 'days_ago' => 104,
                'image' => $this->img('zero-js'), 'caption' => 'Lighthouse after the rewrite',
                'excerpt' => 'Public pages on this blog ship no JavaScript at all. Here is what we gave up, what we gained, and how the page cache fits in.',
                'body' => '<p>Readers come to a blog to read. Everything else, from trackers to carousels, is weight they pay for with time and battery. So the public side of this site is plain server-rendered HTML.</p>'
                    .'<h2>Render once, serve many times</h2><p>Article HTML is processed at save time: headings get ids, images get dimensions, code is highlighted on the server. A request only echoes a stored string.</p>'
                    .'<h2>A static page cache</h2><p>Every public response is written to disk, and nginx serves it without starting PHP. Any content change clears the directory, which is cheap and almost impossible to get wrong.</p>'
                    .$cacheCode
                    .'<h2>What we gave up</h2>'.$this->list(['Client-side search suggestions.', 'Animated page transitions.', 'Comment widgets and share counters.'])
                    .'<p>None of those were missed. Page weight dropped, and the pages feel instant.</p>',
            ],
            [
                'title' => 'Announcing the NanyBot Publishing API',
                'category' => 'product-updates', 'tags' => ['Webhooks', 'Automation', 'Laravel'], 'days_ago' => 91,
                'image' => $this->img('publishing-api'), 'caption' => 'Publishing from a script, no admin login needed',
                'body' => '<p>You can now create, update and delete articles from your own tools. Send HTML in the same format our editor produces and we take care of the rest.</p>'
                    .'<h2>How does authentication work?</h2><p>Each API key belongs to a user and is shown once, when you create it. We only store a SHA-256 hash, so a leaked database does not leak keys.</p>'
                    .'<h2>Can I update an article later?</h2><p>Yes. Send an external id with the first request, then reuse it: the same article is updated instead of duplicated.</p>'
                    .'<h2>Your first request</h2>'.$webhookCode
                    .'<p>Articles arrive as drafts unless you set a status, so you can review them before they go public.</p>',
            ],
            [
                'title' => 'Designing Bot Conversations People Finish',
                'category' => 'guides', 'tags' => ['Chatbots', 'AI'], 'days_ago' => 78,
                'image' => $this->img('conversation-design'), 'caption' => 'A flow map with one clear exit per step',
                'body' => '<p>Most abandoned chats are not caused by bad answers. They are caused by too many questions before the first useful one.</p>'
                    .'<h2>Lead with value</h2><p>Open with something the visitor can use. A short answer and a follow-up beats a form with five required fields.</p>'
                    .'<h2>Keep every turn short</h2>'.$this->list(['One idea per message.', 'Offer two or three quick replies, not ten.', 'Always show how to reach a person.'], true)
                    .$this->figure('flow-map', 'A conversation flow map', 'Every step has an exit to a human')
                    .'<h2>Measure the ending</h2><p>Track how often chats end with a solved problem rather than a closed window. That single number tells you more than message counts ever will.</p>',
            ],
            [
                'title' => 'Moving Slow Work Off the Request With Laravel Queues',
                'category' => 'tutorials', 'tags' => ['Laravel', 'PHP', 'Performance'], 'days_ago' => 66,
                'body' => '<p>If a request sends email, resizes an image and calls two APIs, the visitor waits for all of it. Queues let you answer first and finish the rest in the background.</p>'
                    .'<h2>Write the job</h2><p>A job is a small class with a handle method. Pass it only the data it needs, ideally a model, and let the queue serialize it.</p>'
                    .$queueCode
                    .'<h2>Dispatch and forget</h2><p>Call <code>SendTranscript::dispatch($conversation)</code> from your controller and return immediately. A worker picks the job up within moments.</p>'
                    .'<h2>Plan for failure</h2>'.$this->list(['Set a sensible number of tries and a backoff.', 'Make jobs idempotent so a retry is safe.', 'Watch the failed jobs table, and alert when it grows.']),
            ],
            [
                'title' => 'Release Notes: Smarter Handoff to Human Agents',
                'category' => 'product-updates', 'tags' => ['Chatbots', 'Automation'], 'days_ago' => 55,
                'image' => $this->img('handoff'), 'caption' => 'The new handoff panel',
                'body' => '<p>When a bot cannot help, the worst thing it can do is keep trying. This release makes the switch to a person fast and graceful.</p>'
                    .'<h2>What is new</h2>'.$this->list(['Handoff rules based on confidence, topic or sentiment.', 'The full transcript is attached to the ticket.', 'Visitors see an honest wait time instead of a spinner.'])
                    .'<h2>What changed under the hood</h2><p>Handoff events now fire a webhook, so you can route conversations into the tools your team already uses.</p>'
                    .$this->figure('handoff-panel', 'Handoff settings panel', 'Choose when the bot steps aside'),
            ],
            [
                'title' => 'A Practical Guide to Intent Routing',
                'category' => 'guides', 'tags' => ['AI', 'Chatbots'], 'days_ago' => 47,
                'image' => $this->img('intent-routing'), 'caption' => 'From message to the right queue',
                'body' => '<p>Intent routing decides where a message should go before anyone tries to answer it. Even a simple router saves your team a lot of manual sorting.</p>'
                    .'<h2>Start with rules</h2><p>Before reaching for a model, write down the obvious cases. Keywords will not be perfect, but they are fast, free and easy to debug.</p>'
                    .$intentCode
                    .'<h2>Add a model where rules fall short</h2><p>Use a classifier for the messages your rules label as general. Return a structured result so the rest of your system can act on it.</p>'
                    .$jsonCode
                    .'<p>Log every routing decision. When a customer says the bot got it wrong, you will want to see exactly why.</p>',
            ],
            [
                'title' => 'Why Our Sitemap Includes Images',
                'category' => 'engineering', 'tags' => ['SEO', 'Performance'], 'days_ago' => 39,
                'body' => '<p>Search engines find pages through links, and images through pages. A sitemap with image entries gives them a direct list of what to index.</p>'
                    .'<h2>What goes in each entry</h2><p>Each article lists its URL, last modification date and its featured image. We build the file from the same query the blog index uses, so it never drifts.</p>'
                    .'<h2>Keep it honest</h2>'.$this->list(['Only published articles appear.', 'Scheduled posts show up the minute they go live.', 'Drafts never leak, even by accident.'])
                    .'<p>The file is cached with everything else and cleared whenever content changes.</p>',
            ],
            [
                'title' => 'Webhooks 101: Receiving Events Safely',
                'category' => 'tutorials', 'tags' => ['Webhooks', 'Laravel', 'PHP'], 'days_ago' => 31,
                'image' => $this->img('webhooks-101'), 'caption' => 'An event travelling from sender to handler',
                'body' => '<p>A webhook is just an HTTP request someone else decides to send you. That makes it useful, and a little dangerous.</p>'
                    .'<h2>Verify the sender</h2><p>Check a signature or a bearer token on every request. Compare secrets with a constant-time function so timing does not leak anything.</p>'
                    .'<h2>Respond fast, work later</h2><p>Return a 2xx as soon as you have stored the event, then do the real work in a queued job. Senders retry on slow responses, and you do not want duplicates.</p>'
                    .'<h2>Be idempotent</h2><p>Store the event id and ignore ones you have seen. Retries are a fact of life, not an error.</p>'
                    .$this->figure('webhook-flow', 'Webhook event flow diagram', 'Store first, process later'),
            ],
            [
                'title' => 'Tagging Content Without Creating a Mess',
                'category' => 'guides', 'tags' => ['SEO', 'Automation'], 'days_ago' => 24,
                'body' => '<p>Tags are easy to create and hard to clean up. A few rules at the start keep your archive useful as it grows.</p>'
                    .'<h2>Prefer fewer, broader tags</h2><p>If a tag will only ever hold one article, it is a keyword, not a tag. Aim for tags that readers would actually browse.</p>'
                    .'<h2>Normalize on the way in</h2><p>Match tags by slug so that Remote Work and remote work are the same thing. NanyBot does this for you, including for tags sent through the API.</p>'
                    .$this->list(['Use singular or plural consistently.', 'Review the tag list every quarter.', 'Merge near-duplicates instead of deleting them.']),
            ],
            [
                'title' => 'Caching Public Pages With nginx and Laravel',
                'category' => 'engineering', 'tags' => ['Performance', 'Laravel'], 'days_ago' => 17,
                'image' => $this->img('page-cache'), 'caption' => 'Cache hits never touch PHP',
                'body' => '<p>The fastest PHP request is the one that never starts. For pages that look the same for everyone, a file on disk is hard to beat.</p>'
                    .'<h2>How the cache works</h2><p>A middleware writes each successful public GET response to a file whose path mirrors the URL. nginx tries that file first and only falls back to PHP when it is missing.</p>'
                    .'<h2>Invalidation</h2><p>Saving or deleting any article, category, tag or author clears the cache. Pages rebuild on the next visit.</p>'
                    .$this->code('bash', "php artisan blog:cache-clear\nphp artisan articles:publish-scheduled")
                    .'<h2>What not to cache</h2>'.$this->list(['Anything behind a login.', 'Responses that set cookies.', 'Search results and other query-string pages.']),
            ],
            [
                'title' => 'What We Learned From 10,000 Support Chats',
                'category' => 'guides', 'tags' => ['AI', 'Chatbots'], 'days_ago' => 9,
                'image' => $this->img('support-chats'), 'caption' => 'Most questions repeat, a few matter enormously',
                'excerpt' => 'Ten thousand real conversations, three surprises, and what they mean for how you set up your own bot.',
                'body' => '<p>We read a lot of chats so you do not have to. Here is what stood out.</p>'
                    .'<h2>Most questions are repeats</h2><p>About two thirds of conversations covered the same dozen topics. Covering those well is the cheapest win available.</p>'
                    .'<h2>Short answers win</h2><p>Replies under fifty words were rated helpful far more often than longer ones, even when the long reply contained more detail.</p>'
                    .'<h2>People forgive honesty</h2><p>When the bot said it did not know and offered a person, satisfaction stayed high. A confident wrong answer was the thing that hurt.</p>'
                    .$this->figure('chat-topics', 'Bar chart of chat topics', 'A dozen topics cover most chats'),
            ],
            [
                'title' => 'Release Notes: Scheduled Publishing',
                'category' => 'product-updates', 'tags' => ['Automation', 'Laravel'], 'days_ago' => 3,
                'body' => '<p>You can now write an article today and have it appear on the day you choose, without logging in to press a button.</p>'
                    .'<h2>How it works</h2><p>Set the status to scheduled and pick a date and time. A scheduler checks every minute and publishes anything that is due.</p>'
                    .'<h2>Good to know</h2>'.$this->list(['Scheduled posts are invisible until their time arrives.', 'The page cache is cleared the moment a post goes live.', 'Changing the date moves the post, and turning it back to draft cancels it.']),
            ],
            [
                'title' => 'Draft: Planning Next Quarter on the Blog',
                'category' => 'engineering', 'tags' => ['SEO'], 'status' => 'draft', 'days_ago' => 0,
                'body' => '<p>Notes for the roadmap post. Not ready for readers yet.</p><h2>Topics to cover</h2>'.$this->list(['Editor improvements.', 'Author profiles.', 'Better analytics without trackers.']),
            ],
            [
                'title' => 'Draft: Rewriting the Editor Toolbar',
                'category' => 'tutorials', 'tags' => ['PHP', 'Performance'], 'status' => 'draft', 'days_ago' => 0,
                'body' => '<p>Half-finished thoughts on why the toolbar was rebuilt and what changed for authors.</p>',
            ],
            [
                'title' => 'Coming Soon: The Year in Bots',
                'category' => 'guides', 'tags' => ['AI', 'Chatbots', 'Automation'], 'status' => 'scheduled', 'in_days' => 7, 'days_ago' => 0,
                'image' => $this->img('year-in-bots'), 'caption' => 'A year of conversations, summarised',
                'body' => '<p>A look back at twelve months of conversations, launches and lessons, scheduled to go out next week.</p>'
                    .'<h2>The highlights</h2>'.$this->list(['Our busiest day ever.', 'The feature everyone asked for.', 'What we are building next.'])
                    .'<p>Check back soon for the full story.</p>',
            ],
        ];
    }
}

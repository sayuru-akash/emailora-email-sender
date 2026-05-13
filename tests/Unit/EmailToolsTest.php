<?php

namespace Tests\Unit;

use App\Models\Contact;
use App\Services\Email\EmailPersonalizer;
use App\Services\Email\EmailSanitizer;
use App\Services\Email\HtmlToText;
use PHPUnit\Framework\TestCase;

class EmailToolsTest extends TestCase
{
    public function test_personalizer_replaces_known_contact_variables(): void
    {
        $contact = new Contact([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'company' => 'Example Co',
        ]);

        $html = (new EmailPersonalizer)->render('Hello {first_name} at {company}', $contact);

        $this->assertSame('Hello Jane at Example Co', $html);
    }

    public function test_sanitizer_removes_scripts_and_event_handlers(): void
    {
        $html = (new EmailSanitizer)->sanitize('<p onclick="bad()">Hi</p><script>alert(1)</script>');

        $this->assertSame('<p>Hi</p>', $html);
    }

    public function test_html_to_text_creates_readable_fallback(): void
    {
        $text = (new HtmlToText)->convert('<p>Hello</p><p>World</p>');

        $this->assertStringContainsString("Hello\nWorld", $text);
    }
}

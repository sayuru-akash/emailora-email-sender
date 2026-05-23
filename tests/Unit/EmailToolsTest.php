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
            'metadata' => ['student_id' => 'CCA-100'],
        ]);

        $html = (new EmailPersonalizer)->render('Hello {{ name }} at {company} / {{ metadata.student_id }} / {{ unsubscribe_url }}', $contact, [
            'unsubscribe_url' => 'https://example.com/unsubscribe',
        ]);

        $this->assertSame('Hello Jane Doe at Example Co / CCA-100 / https://example.com/unsubscribe', $html);
    }

    public function test_personalizer_reports_only_unknown_variables(): void
    {
        $unknown = (new EmailPersonalizer)->unresolvedVariables('Hello {{ name }} {{ metadata.student_id }} {unsubscribe_url} {{ nope }}');

        $this->assertSame(['nope'], $unknown);
    }

    public function test_personalizer_can_limit_metadata_variables_to_dataset_keys(): void
    {
        $unknown = (new EmailPersonalizer)->unresolvedVariables(
            'Hello {{ metadata.student_id }} {{ metadata.stduent_id }}',
            ['student_id'],
        );

        $this->assertSame(['metadata.stduent_id'], $unknown);
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

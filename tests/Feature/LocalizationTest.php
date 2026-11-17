<?php

namespace Tests\Feature;

use App\Support\Localize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_browser_route_renders_application_with_404_and_no_store(): void
    {
        $this->withoutVite()->get('/unknown-browser-page')->assertNotFound()->assertSee('id="app"', false)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_validation_and_unavailable_responses_follow_language_without_changing_codes(): void
    {
        foreach (['en' => ['Review the highlighted fields.', 'The email field is required.', 'The requested resource is unavailable.'],
            'zh-CN' => ['请检查标记的字段。', '请填写邮箱。', '请求的资源不可用。'],
            'ja' => ['強調表示された項目を確認してください。', 'メールアドレスは必須です。', '指定されたリソースは利用できません。']] as $locale => $expected) {
            $this->withHeader('Accept-Language', $locale)->postJson('/api/auth/login', [])
                ->assertUnprocessable()->assertJsonPath('code', 'validation_error')
                ->assertJsonPath('message', $expected[0])->assertJsonPath('errors.email.0', $expected[1])
                ->assertJsonPath('error_keys.email.0', 'The email field is required.');
            $this->getJson('/api/public/nonexistent-fictional-slug')->assertNotFound()
                ->assertJsonPath('message', $expected[2])->assertJsonPath('code', 'unavailable');
        }
    }

    public function test_parameterized_rules_region_variants_and_language_fallback(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ja-JP,en;q=0.8');
        $this->assertSame('パスワードは12文字以上で入力してください。', Localize::message('The password field must be at least 12 characters.', $request));
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'zh-TW');
        $this->assertSame('此字段必填。', Localize::message('This field is required.', $request));
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr');
        $this->assertSame('This field is required.', Localize::message('This field is required.', $request));
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ja');
        $this->assertSame('Original user text', Localize::message('Original user text', $request));
    }
}

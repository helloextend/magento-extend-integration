<?php

namespace Extend\Integration\Test\Unit\Etc;

use PHPUnit\Framework\TestCase;

class CspWhitelistTest extends TestCase
{
    private const CONTENT_ENVIRONMENTS = ['prod', 'demo', 'dev', 'stage', 'platformsandbox', 'offerssandbox'];

    private const REGIONAL_HOST_PATTERN = '/\.s3\.[a-z]{2}-[a-z-]+-\d+\.amazonaws\.com$/';

    private const PATH_STYLE_HOST = 'https://s3.amazonaws.com/';

    /**
     * @var \SimpleXMLElement
     */
    private $whitelist;

    protected function setUp(): void
    {
        $whitelist = simplexml_load_file(__DIR__ . '/../../../etc/csp_whitelist.xml');
        $this->assertNotFalse($whitelist, 'csp_whitelist.xml could not be parsed');
        $this->whitelist = $whitelist;
    }

    /**
     * A CSP source expression carrying a path matches that exact path unless the path ends in a
     * slash, in which case it matches as a prefix. Pinning a filename therefore stops working the
     * moment the asset is re-uploaded under a new name.
     */
    public function testImageSourcesAreNotPinnedToASpecificFile()
    {
        $checked = 0;

        foreach ($this->getImageSourceValues() as $id => $value) {
            // A scheme is optional in a CSP source expression, but parse_url() reads a scheme-less
            // value as one long path, so give it a scheme before asking for the path.
            if (strpos($value, '://') === false) {
                $value = 'https://' . $value;
            }

            $path = parse_url($value, PHP_URL_PATH);
            if ($path === null || $path === false || $path === '' || $path === '/') {
                continue;
            }

            $checked++;
            $this->assertStringEndsWith(
                '/',
                $path,
                sprintf(
                    'img-src value "%s" pins the exact path "%s". Use a bare host, or a path '
                    . 'prefix ending in "/", so a re-uploaded asset still resolves.',
                    $id,
                    $path
                )
            );
        }

        $this->assertGreaterThan(0, $checked, 'no img-src value carried a path, so nothing was asserted');
    }

    /**
     * S3 serves these buckets under three addressing forms and the offer API has emitted two of
     * them over the years, so all three are whitelisted per environment. Asserting the shape of
     * each form is what catches a regression back to a single form.
     */
    public function testContentBucketsAreWhitelistedInEveryAddressingForm()
    {
        $values = $this->getImageSourceValues();

        foreach (self::CONTENT_ENVIRONMENTS as $environment) {
            $regional = $values['extend-content-' . $environment] ?? null;
            $global = $values['extend-content-' . $environment . '-global'] ?? null;
            $pathStyle = $values['extend-content-' . $environment . '-legacy'] ?? null;

            $this->assertNotNull($regional, sprintf('missing %s regional virtual-hosted bucket', $environment));
            $this->assertNotNull($global, sprintf('missing %s global virtual-hosted bucket', $environment));
            $this->assertNotNull($pathStyle, sprintf('missing %s path-style bucket', $environment));

            $this->assertMatchesRegularExpression(
                self::REGIONAL_HOST_PATTERN,
                $regional,
                sprintf('%s content bucket is not addressed by its regional virtual-hosted host', $environment)
            );

            $bucket = strstr($regional, '.s3.', true);

            $this->assertSame(
                $bucket . '.s3.amazonaws.com',
                $global,
                sprintf('%s global virtual-hosted host does not name the same bucket', $environment)
            );

            $this->assertSame(
                self::PATH_STYLE_HOST . $bucket . '/',
                $pathStyle,
                sprintf('%s path-style entry is not the same bucket on the S3 path-style host', $environment)
            );
        }

        $this->assertCount(
            3 * count(self::CONTENT_ENVIRONMENTS),
            preg_grep('/^extend-content-/', array_keys($values)),
            'every extend-content entry must belong to a listed environment, in all three forms'
        );
    }

    /**
     * @return string[]
     */
    private function getImageSourceValues(): array
    {
        $values = [];

        foreach ($this->whitelist->policies->policy as $policy) {
            if ((string) $policy['id'] !== 'img-src') {
                continue;
            }

            foreach ($policy->values->value as $value) {
                $values[(string) $value['id']] = trim((string) $value);
            }
        }

        $this->assertNotEmpty($values, 'no img-src values found in csp_whitelist.xml');

        return $values;
    }
}

<?php

namespace tests\unit\Statistics\Calculator;

use PHPUnit\Framework\TestCase;
use SocialPost\Dto\SocialPostTo;
use SocialPost\Hydrator\FictionalPostHydrator;
use Statistics\Calculator\MonthlyPostsPerUser;
use Statistics\Dto\ParamsTo;
use Statistics\Enum\StatsEnum;

class MonthlyPostsPerUserTest extends TestCase
{
    /**
     * @param SocialPostTo[] $posts
     *
     * @dataProvider postsDataProvider
     */
    public function testAccumulate(array $posts, int $expectedCount, string $expectedMonth, float $expectedValue)
    {

        $calculator = new MonthlyPostsPerUser();
        $params = (new ParamsTo())->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
            ->setStartDate(new \DateTime('2018-07-10'))
            ->setEndDate(new \DateTime('2018-09-11'));
        $calculator->setParameters($params);
        foreach ($posts as $post) {
            $calculator->accumulateData($post);
        }

        $result = $calculator->calculate();

        self::assertCount($expectedCount, $result->getChildren(), 'number of children equals number of months in data');

        $checkedChild = array_values(array_filter($result->getChildren(), function($v) use ($expectedMonth) {
            return $v->getSplitPeriod() === $expectedMonth;
        }));

        self::assertCount(1, $checkedChild, 'only 1 child per month');
        self::assertEquals($expectedValue, $checkedChild[0]->getValue(), 'proper vaerage value');
    }

    public function postsDataProvider(): array
    {
        $socialPostSets = [];
        foreach (['social-posts-response.json', 'social-posts-response-2.json'] as $file) {
            $socialPostSets[] = $this->extractPostsFromJsonFile($file);
        }

        return [
            'average 1' => [$socialPostSets[0], 1, 'August 2018', 1],
            'average 1.5, 2 months' => [$socialPostSets[1], 2, 'August 2018', 1.5],
        ];
    }

    /**
     * Get the data for testing calculators from JSON files
     *
     * @param string $filename
     * @return SocialPostTo[]
     */
    private function extractPostsFromJsonFile(string $filename): array
    {
        $postData = json_decode(file_get_contents(__DIR__.'/../../../data/'.$filename), true);
        $socialPosts = [];
        $socialHydrator = new FictionalPostHydrator();
        foreach ($postData['data']['posts'] as $post) {
            $socialPosts[] = $socialHydrator->hydrate($post);
        }

        return $socialPosts;
    }
}

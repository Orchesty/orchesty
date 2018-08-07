<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 7.8.18
 * Time: 16:23
 */

namespace Tests\Live\AppBundle\Model\Systems\Impl\SalesforceApp;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Exception;
use Hanaboso\CommonsBundle\Crypt\CryptService;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SalesforceAppSystemTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\SalesforceApp
 */
final class SalesforceAppSystemTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testReloadToken(): void
    {
        $decrypted = CryptService::decrypt('00_MUIEAFz_vEWfpTtKBtQWk-ULx4LMc-1Tf1v6jBr8edfR4OaAe4AabIgb3msRAIrpPrhX37DbHlO9umObT29Vha9oMqJ9Q-JPK8TrRThoaTam8GC-caf_R7KP4GyS5BAjsOLvj9aacdaUo1hrXwBa8_jQMqsnrJl6nEoDWOdwyTPheJyfp_V9z4qWdSsjaSlgC-1IPGsqUTGUJsUR0_Qu4H_MptE5EWg4CjuNm0EXUs_H1ZeRs7OkgJ01TUGC57d05hHucwTTooEUFv1rIaAn3xXuVe13IkHFW6jdwNdhwIP8w3P_oEe0eicM-ToZxJD_WplljQ5MUlBtS15hPtItX-VtoX0IfJ50kmT47zWtVIXMtS08zhz4LlR53xPWb8OjkDZ4vs_L8Ucd2OQWOu9SvzFjQ_BzHSHTZm7HWj9nLyPK6M0vTmuqYx271z5dc-_1uvjvnI-_nsb4O7w6Q3Ho4cns2gRl-_vNxNEFgXyY1IyabxxGfHw8Ojs-E2LO7k1ohZtW481-sFPungB2TZVZfdreu360H48E0lUhLWtlfdsJ9sf3XQIjhvah4_WvxlBzrB-U83rUxMFzSj9x7mQbxQW5DYZ-prpzTZtXz51yo_p7rci8b6Lxb5Ff-YArGbl0X9mc0bibU46gqBaCVSiEN2HKNqNUIYTeC84ns7yo8q9lgKQryQ-Adc79-tiEYLIkjIFrHcrCbx6AZ31atxbDOuZxeTrom9o9bNEgdVHlBDNx3Flqp0Jpxn8xazry-DTMGqfDnkFO_xpCglrXajeNUaSugxy93aApRNLxvhdWqMe0rLR6KoKU9AcrFZXSkWRzfW73bzzIOPgpVswbV_1LkEWgm7ZbTNicvHr_4F3_nElvAtH_D2Cc6z3dYtvDvxsbl2CKBhC0xfuY-Rt_MWuOJ9bW_2vB0YSa_wDSO5juuC_Q9HdfQYFeU4Xam56gdxM9VW1Y-XHZlPEffgSflpdgXTB_PRPhIaOmHY9xAxKavrPY-bzBRWQbwCmX0Zp2BMt77DTbXhuxbCAhivxoSzSytOAOktJYxFhyh8ru1RgJI0p9ekXnjtWrpIrwfd10PjjmYVjT4mFj5yfKq5p4Oq2IXREIHj4TqorIb4qMunkyU9fnOdzumy9AezcOjP1C2_VFDyMp9XSGVjvUCq3PcWHaZsPhmqBUKTS6CPcgVmG7jsanVuYQk-9DlPkBUkwQEDX9s4SRXSVrbxmZR9e7ShZ2iG4mgjeRAnPg_b1KVcPSXk_UpU1cuqjXxfQfG8aqtJgqTLERvDRpvcweBZN3bvIsaX-iuELUbsG7CuK1eo5OYUkyHDR7fCvv7OxJ1zBcQw8FidwFWMQc_jVHV9gH6WZEoLhK8nMdhLecDQd600p5zqqScFQs_TksG_V4W-t_rA97MuyT8ZdA1W0LICk3PGIYam1vD9kd0Kk60fBQvrUOwpuVPyLA_l1x_TfGdXRvZrN3JCmWzU5qmoelOGfcMtTHalW_gV_1645sD-Xi-YduEIVwpBGGazepNOyJ4A0JzSJ3YyHUk7OkN3jN3snQBtHwsGRHFE6tEngL1bvYwyjM5WnjeVrN_G8oyHvD-ltpjLqa4NctLXBUxR3L1XZ6MDiD_wezbmCjCH8q23bEJAUjtg3RpfxFjanqZLDoiC8fSWm4YEw-jcgv3Su5Ly_BZZFN2ebjIdDUs9x_qGRzMnMAhinnEcpllLOSGvQVRqGHNhceZehLOoSfn0FJVwfvfCCk3FGHzNlMfVZjiPZF9Y1UXM8C42Gh96y-kRzkxYdAy5BULFkg71cd4n13qOsgxy43Df66Chy-J9K6DR7r-qHm56Jw2fiL1szNYD7OdNeIedJPZrxIGTx4MD4vtbQql_K-hgWuvxQre8on79And2fGV0TmtOXPV2IKcvX9vx3vggCya9eeJ3jrzkjPrefuB0mpufkZQ2k8NcjwUzJGl_Pan3GyZFCHnIg4XPwt2Nq8m2yLpNsQ6OmgHcEzIVV4tHw4lOuOPbIyIOcJn3dCt-jHj1q-2dY-IdjfyipNn16iKvHS0-XIziTjgvZldvF4G1_94vbOhFkNlfF0-5tSe7s5GsP93q0FqpMPWYhplLwVsOuw4ZOXDnrkDYY_yrJKh0svJwMEif9WDCSwcSeaUohiPnZWVfWX7k5nd3pJpXBcW3RJaqtDPt5Mwcp1rC3vPfSCS7fypW06Z4fo32_VMKGZ8jmgsQlwLG2-XK6Yo8MLZZ8ggrjPeswj0wIXY6ZPD4F5YlfDmQfUB_9sdVAegsliXYteQA4duKxINoEcoJDL53WZv8N6KMnqDTs_T8jQu9X2-WDx_9CNSa5hvkU9Vx8nOLJ2Ji-TqCvBgyDEQPR3oxfDCYGuQVkgmT7UtYyhkBejkrnIthXjKNoU3CIE6se2Ed5AEdbdy9RxN9NyCK8WSxqZxrPUZ4LBF_q8yv5W_O4vs7igquG6yOthtMXklCcs2GvB1FqSkdpVBrzRSkkPnwMMARsE8eJfZpMNKbilo6eVn0G13BoOr8MOa6E0YIR8qxvyK7OMpVqUbb3Y_zU8zuru5iDYRLwcmhqVJ-uX2htNr7T3oVVdqGZ-HSeVygTh1uUUEup0xBdfsFZf_0D_OWZnh3WweCTe4tWXL8y_a8htsCYTuG7OZWu3Ti81PsPBkyZ3WknTV5E3IGjcHzw9dDL0Fg0DWTqzZkQ3wJoErzH0cV-16FafbjTMjNuabgPFZZLyzBCAsNQEiAN63uXZgA9tAVI1P6UOJLSNbe0oF-E6ayI5z2J95S3YmoQChmXfORKYMEu12EeveMOxBbLo4DCvu8nKN4awt6YP4PT-YpwN7pokK0UG80FRRDKzvJACAb_z0vOwzSuMD41OXho4TFeQkgKI');

        $sys = $this->ownContainer->get('systems.salesforceapp');

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setSystem($sys->getKey())
            ->setUser('1')
            ->setSettings($decrypted);

        $token = $sys->refreshToken($systemInstall)->getSettings()['access_token'];

        self::assertEquals(
            $token,
            '00D1r000000qydj!AREAQBc7UKNAbopJ1VNlDEdpgRw2bpTkeiKTjRujydPR5nR91GzGtHO.czZYfGbMxfLwl05d6ZbGSFMd93M8BaRxJoYdqCFw'
        );
    }

}
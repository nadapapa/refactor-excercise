<?php
declare(strict_types=1);

namespace Tests;

use App\Entities\Discount\AbstractDiscountCalculator;
use App\Helpers\UserManager;
use App\Models\Admin;
use App\Models\ProductMacDiscount;
use App\Models\ProductStock;
use App\Models\ProductWarehouse;
use App\Services\LoginService;
use Doctrine\DBAL\Query\QueryException;
use Faker\Factory;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Contracts\Translation;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductBrandI18n;
use App\Models\ProductCategory;
use App\Models\ProductCategoryI18n;
use App\Models\ProductColor;
use App\Models\ProductColorI18n;
use App\Models\ProductCountry;
use App\Models\ProductCountryI18n;
use App\Models\ProductI18n;
use App\Models\ProductPriceList;
use App\Models\ProductProductPriceList;
use App\Models\ProductRegion;
use App\Models\ProductRegionI18n;
use App\Models\ProductTag;
use App\Models\ProductTagI18n;
use App\Models\ProductType;
use App\Models\ProductTypeI18n;
use App\Models\ProductUnit;
use Database\Seeders\BFAdminSeeder;
use GraphQL\Language\AST\EnumValueNode;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use JetBrains\PhpStorm\ArrayShape;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use stdClass;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use MakesGraphQLRequests;
    use ElasticTestTrait;

    protected Admin $bfAdmin;
    protected ProductPriceList $singlePriceList;
    protected ProductPriceList $clubPriceList;
    protected CustomerGroup $clubCustomerGroup;
    protected CustomerGroup $specialCustomerGroup;
    protected CustomerGroup $shopCustomerGroup;

    public const DEFAULT_PASSWORD = 'testing1';

    protected const WEBSHOP_LIST_PRODUCT_QUERY = '
            id
            is_shelf
            is_active
            mac_is_active
            is_link_exclusive
            mac_brand_title
            title
            slugs {
                language
                slug
            }
            image_url
            image_title
            image_ratio
            list_image_url
            list_image_title
            is_borrowable
            is_fine_wine
            cart_max_limit
            shop_stock_limit
            stock_orderable
            is_special_selection
            weight
            net_weight
            mac_code
            vintage_year
            price
            club_price
            is_discount_price
            is_orderable
            badges {
                id
                title
                font_color
                background_color
            }
            product_category {
                id
                title
                slugs {
                    language
                    slug
                }
            }
            product_country{
                id
                title
                slugs {
                    language
                    slug
                }
            }
            product_region{
                id
                title
                slugs {
                    language
                    slug
                }
            }
            main_category
        ';

    protected const WEBSHOP_PRODUCT_QUERY = '
        id
        main_category
        is_shelf
        mac_brand_title
        title
        description
        is_active
        mac_is_active
        is_link_exclusive
        slugs {
            language
            slug
        }
        image_url
        image_ratio
        list_image_url
        list_image_title
        image_title
        og_title
        og_description
        meta_description
        og_image
        title_short
        combination
        is_borrowable
        is_fine_wine
        cart_max_limit
        shop_stock_limit
        is_orderable
        stock_orderable
        shop_stock_available
        is_special_selection
        weight
        net_weight
        price
        club_price
        is_discount_price
        badges {
            id
            title
            font_color
            background_color
        }
        product_category {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_country {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_region {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_type {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_brand {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_color {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_tags {
            id
            title
            slugs {
                language
                slug
            }
        }
        wine_type {
            id
            title
            slugs {
                language
                slug
            }
        }
        product_unit
        has_components
        components {
            piece
            product {
                id
                title
                is_active
                mac_is_active
                is_link_exclusive
                slugs {
                    language
                    slug
                }
                price
                club_price
                is_discount_price
                image_url
                image_title
                list_image_url
                list_image_title
                image_ratio
                main_category
            }
        }
        brand {
            id
            is_active
            position
            mac_ordinal
            mac_title
            mac_title_value
            created_at
            updated_at
            is_visible_in_menu
            main_category
            i18ns {
                language
                title
                slug
                content_title
                description_short
                description
                meta_description
                image_url
                og_image_url
                og_description
                image_title
                og_title
            }
        }
        recommendedProducts {
            id
            title
            mac_brand_title
            main_category
            slugs {
                language
                slug
            }
        }
        substitute_product {
            id
            title
            mac_brand_title
            main_category
            slugs {
                language
                slug
            }
        }
    ';

    const CHECKOUT_QUERY_RESPONSE = '
        sub_total
        discount_total
        grand_total
        cart {
            %s
        }
        is_cart_changed
        payment_method {
            selected_payment_method {
                type
                ... on WebshopCashOnDeliveryPaymentMethod { cost }
                ... on WebshopBankTransferPaymentMethod { bank_account }
            }
            selected_saved_credit_card {
                id
                card_title
                card_type
                card_pan
                card_exp
                is_default
            }
            save_credit_card
        }
        discounts {
          id
          discount_type
          title
          amount
        }
        billing_address {
            type
            first_name
            last_name
            company_name
            tax_number
            email
            phone
            country
            postcode
            city
            street
            street_type
            house_number
            foreign_address
        }
        coupons {
          code
          discount {
              id
              discount_type
              title
              amount
          }
          is_valid
          discount_amount
        }
        gift_card {
          code
          value
        }
        guest_data {
            first_name
            last_name
            email
        }
        shipping_method {
            selected_shipping_method {
                __typename
                type
                cost
                available_services
                ... on HomeDeliveryShippingMethod {
                    express_cost
                }
            }
            selected_services
            home_delivery_data {
                shipping_address {
                    type
                    first_name
                    last_name
                    company_name
                    tax_number
                    email
                    phone
                    place {
                        country
                        hungarian_address {
                            house_number
                            public_place {
                                postcode
                                city
                                street
                                street_type
                            }
                        }
                        foreign_address
                    }
                }
                is_express
                selected_delivery_interval {
                    date
                    day_of_week
                    start_hour
                    end_hour
                }
                countryside_delivery {
                    id
                    title
                    sales_emails
                    delivery_text_hu
                    delivery_text_en
                    is_default_delivery
                    is_credit_card_allowed
                    is_cash_on_delivery_allowed
                    is_bank_transfer_allowed
                }
            }

            foxpost_address {
                postcode
                city
                street
            }
            contact_phone_number
            store {
                store_id
                language
                title
                slug
                open_hours
                description
                contact_info
                lat
                lng
                meta_description
                image_url
                og_image_url
                og_description
                image_title
                og_title
                paymentgateway_account
            }
            selected_postcard_id
            selected_postcard_title
            postcard_comment
            shipping_comment
            wine_school_gift_data {
                name
                phone
                email
                message
            }
        }
    ';

    const QUERY_APPLY_COUPON = '
        mutation ($code: String!) {
          checkoutApplyCoupon(code: $code) {
            %s
          }
        }
    ';

    const QUERY_ADD_PRODUCT = '
        mutation ($product_id: Int! $quantity: Int!) {
          cartAddProduct(product_id: $product_id quantity: $quantity) {
            %s
          }
        }
    ';

    const QUERY_SET_WINE_SCHOOL_EVENT = '
        mutation ($event_id: Int! $quantity: Int!) {
          cartSetWineSchoolEvent(wine_school_event_id: $event_id quantity: $quantity) {
            %s
          }
        }
    ';

    const QUERY_SET_WINE_SCHOOL_PACKAGE = '
        mutation ($package_id: Int! $quantity: Int!) {
          cartSetWineSchoolPackage(wine_school_package_id: $package_id quantity: $quantity) {
            %s
          }
        }
    ';

    const QUERY_SET_PRODUCT = '
        mutation ($product_id: Int! $quantity: Int!) {
          cartSetProduct(product_id: $product_id quantity: $quantity) {
            %s
          }
        }
    ';

    const WEBSHOP_WINE_SCHOOL_THEME_QUERY = '
        id
        theme_type
        title
        slug
        is_online
        product {'.self::WEBSHOP_LIST_PRODUCT_QUERY.'}
        list_description
        description
        image_url
        image_title
        list_image_url
        list_image_title
        og_image_url
        og_title
        og_description
        meta_description
        is_free_seats
        events {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
        related_products {'.self::WEBSHOP_PRODUCT_QUERY.'}
    ';

    const WEBSHOP_WINE_SCHOOL_EVENT_QUERY = '
        id
        wine_school_theme {
            id
            theme_type
            title
            slug
            is_online
        }
        address
        location
        wine_school_host {
            id
            title
            description
            image_url
            image_title
            standing_image_url
            standing_image_title
        }
        max_seats
        free_seats
        is_gift
        start_at
        price
        club_price
        is_free_seats
    ';

    const CART_QUERY_RESULT = '
        total
        sub_total
        discount_total
        free_delivery
        items {
            __typename
            id
            price
            price_for_user
            price_original
            club_price
            ... on CartItem {
                discount_amount
                discounts {
                    id
                    discount_type
                    title
                    amount
                }
            }
            has_substitute
            substitute_product {
                __typename
                ... on WebshopProduct {'.self::WEBSHOP_LIST_PRODUCT_QUERY.'}
                ... on WebshopWineSchoolEvent {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
                ... on WebshopWineSchoolPackage {
                    id
                    title
                    slug
                    validity_months
                    discount
                    description
                    package_image_url: image_url
                    package_list_image_url: list_image_url
                    image_title
                    list_image_title
                    events {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
                    og_image_url
                    og_title
                    og_description
                    meta_description
                    is_free_seats
                }
            }
            product {
                __typename
                ... on WebshopProduct {'.self::WEBSHOP_LIST_PRODUCT_QUERY.'}
                ... on WebshopWineSchoolEvent {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
                ... on WebshopWineSchoolPackage {
                    id
                    title
                    slug
                    validity_months
                    discount
                    description
                    package_image_url: image_url
                    package_list_image_url: list_image_url
                    image_title
                    list_image_title
                    events {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
                    og_image_url
                    og_title
                    og_description
                    meta_description
                    is_free_seats
                }
            }
        }
    ';

    public const GUEST_DATA = '
        first_name: "Elek"
        last_name: "Teszt"
        email: "tesztelek@tesztelek.hu"
    ';

    protected const BILLING_ADDRESS = '
        billing_address: {
            type: PRIVATE
            first_name: "Fakka"
            last_name: "Makka"
            email: "fakka@makka.hu"
            phone: "11111111"
            country: HUNGARY
            postcode: 1111
            city: "Fakka"
            street: "Fakka"
            street_type:  "út"
            house_number: "42"
        }
    ';

    const ORDER_RESPONSE = '
        id
        old_id
        language
        customer_number
        user {
            id
            email
            first_name
            last_name
            phone
            customer_number
            created_at
            last_login
            default_language
            has_password
        }
        status
        sub_total
        discount_total
        grand_total
        items {
            id
            product {
                __typename
                ... on WebshopProduct {'.self::WEBSHOP_LIST_PRODUCT_QUERY.'}
                ... on WebshopWineSchoolEvent {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
                ... on WebshopWineSchoolPackage {
                    id
                    title
                    slug
                    validity_months
                    discount
                    description
                    package_image_url: image_url
                    package_list_image_url: list_image_url
                    image_title
                    list_image_title
                    events {'.self::WEBSHOP_WINE_SCHOOL_EVENT_QUERY.'}
                    og_image_url
                    og_title
                    og_description
                    meta_description
                    is_free_seats
                }
            }
            sum_price
            price_original
            user_price
            discount_amount
            quantity
        }
        discounts {
            id
            title
            amount
            discount_type
        }
        coupons {
            code
            is_valid
            discount_amount
            discount {
                id
                discount_type
                title
                amount
            }
        }
        gift_card {
            code
            value
        }
        guest_data {
            first_name
            last_name
            email
        }
        billing_address {
            type
            first_name
            last_name
            company_name
            tax_number
            email
            phone
            country
            postcode
            city
            street
            street_type
            house_number
            foreign_address
        }
        payment_method {
            type
            cost
            bank_account
            save_credit_card
            selected_saved_credit_card {
                id
                card_title
                card_type
                card_pan
                card_exp
                is_default
            }
            currency
            currency_rate
        }
        shipping_method {
            cost
            express_cost
            type
            selected_services
            home_delivery_data {
                shipping_address {
                    type
                    first_name
                    last_name
                    company_name
                    tax_number
                    email
                    phone
                    place {
                        country
                        hungarian_address {
                            house_number
                            public_place {
                                postcode
                                city
                                street
                                street_type
                            }
                        }
                    }
                }
                is_express
                selected_delivery_interval {
                    date
                    day_of_week
                    start_hour
                    end_hour
                }
                countryside_delivery {
                    id
                    title
                    sales_emails
                    delivery_text_hu
                    delivery_text_en
                    is_default_delivery
                    is_credit_card_allowed
                    is_cash_on_delivery_allowed
                    is_bank_transfer_allowed
                }
            }
            foxpost_address {
                id
                postcode
                city
                street
            }
            contact_phone_number
            store {
                store_id
                language
                title
                slug
                open_hours
                description
                contact_info
                lat
                lng
                meta_description
                image_url
                og_image_url
                og_description
                image_title
                og_title
                paymentgateway_account
            }
            selected_postcard_id
            selected_postcard_title
            postcard_comment
            shipping_comment
            wine_school_gift_data {
                name
                phone
                email
                message
            }
        }
        delivery_address
        contact_name
        discount_titles
        provider_transaction_id
        created_at
        updated_at
        maconomy_id
        maconomy_sync_at
    ';

    const CHECKOUT_SUBMIT_RESPONSE = '
        rewrite_url
        transaction_id
        guest_can_register
        order {' . self::ORDER_RESPONSE. '}';

    public function runBare(): void {
        $retryCount = 1;
        for ($i = 0; $i < $retryCount; $i++) {
            try {
                parent::runBare();
                return;
            }
            catch (\Exception $e) {
                // last one thrown below
            }
        }
        if ($e) {
            throw $e;
        }
    }

    protected static function assertError(string $errorMessage, array $responseData)
    {
        self::assertArrayHasKey('errors', $responseData, print_r($responseData, true));
        self::assertCount(1, $responseData['errors']);
        self::assertEquals($errorMessage, $responseData['errors'][0]['message'], print_r($responseData, true));
    }

    protected static function assertUnauthorized(array $responseData)
    {
        self::assertError(__('auth.not_authorized'), $responseData);
    }

    protected static function assertUnauthenticated(array $responseData)
    {
        self::assertError(AuthenticationException::MESSAGE, $responseData);
    }

    protected static function assertNoError(array $responseData)
    {
        self::assertArrayNotHasKey('errors', $responseData, print_r($responseData, true));
    }

    protected static function assertValidationError($responseData, $invalidField, $message = null)
    {
        self::assertArrayHasKey('errors', $responseData, print_r($responseData, true));
        self::assertCount(1, $responseData['errors'], print_r($responseData, true));
        self::assertEquals('validation', $responseData['errors'][0]['extensions']['category']);

        $validationErrors = $responseData['errors'][0]['extensions']['validation'];
        $fieldErrors = Arr::get($validationErrors, $invalidField, null);

        self::assertNotNull($fieldErrors);
        self::assertCount(1, $fieldErrors);

        if ($message) {
            self::assertEquals($message, $fieldErrors[0]);
        }
    }

    public function activeProvider(): array
    {
        return [
            'all-all' => [null, null],
            'past-all' => [\Carbon\Carbon::now()->subDay(), null],
            'all-future' => [null, Carbon::now()->addDay()],
            'past-future' => [Carbon::now()->subDays(2), Carbon::now()->addDay()],
        ];
    }

    public function languageProvider(): array
    {
        return [
            [Translation::LANG_HU],
            [Translation::LANG_EN],
        ];
    }

    public function inactiveProvider(): array
    {
        return [
            'all-all-active' => [null, null, false],
            'past-all-inactive' => [Carbon::now()->subDay(), null, false],
            'future-all-active' => [Carbon::now()->addDay(), null, true],
            'future-all-inactive' => [Carbon::now()->addDay(), null, false],
            'all-past-active' => [null, Carbon::now()->subDay(), true],
            'all-past-inactive' => [null, Carbon::now()->subDay(), false],
            'all-future-inactive' => [null, Carbon::now()->addDay(), false],
            'past-past-active' => [Carbon::now()->subDays(2), Carbon::now()->subDay(), true],
            'past-past-inactive' => [Carbon::now()->subDays(2), Carbon::now()->subDay(), false],
            'future-future-active' => [Carbon::now()->addDay(), Carbon::now()->addDays(2), true],
            'future-future-inactive' => [Carbon::now()->addDay(), Carbon::now()->addDays(2), false],
            'past-future-inactive' => [Carbon::now()->subDays(2), Carbon::now()->addDay(), false],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => null]);
        UserManager::$cacheUser = false;
        AbstractDiscountCalculator::$cacheProductSegment = false;
        $this->seed(BFAdminSeeder::class);
        $this->bfAdmin = Admin::query()->where('email', BFAdminSeeder::ADMIN_EMAIL)->first();
        Redis::flushdb();
    }

    protected function convertArrayToGraphQlData(array $data = [], string $separate = ' '): string
    {
        $testData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $qlValue = $key . ':[';
                if (!empty($value)) {
                    foreach ($value as $field => $arr) {
                        if (is_numeric($arr)) {
                            $qlValue .= $arr . ',';
                        } else {
                            $qlValue .= $this->convertArrayToGraphQlData($arr) . ',';
                        }
                    }
                    $qlValue = substr($qlValue, 0, -1);
                }
                $qlValue .= ']';
                $testData[] = $qlValue;
            } else {
                if (is_string($value)) {
                    $testData[] = $key . ':"' . str_replace(['"', "\n"], ['\"', '\n'], $value) . '"';
                } else {
                    if ($value === null) {
                        $testData[] = $key . ':null';
                    } else {
                        if ($value === true) {
                            $testData[] = $key . ':true';
                        } else {
                            if ($value === false) {
                                $testData[] = $key . ':false';
                            } else {
                                if ($value instanceof EnumValueNode) {
                                    $testData[] = $key . ': ' . $value->value;
                                } else {
                                    if ($value instanceof stdClass) {
                                        $testData[] = $key . ': ' . $this->convertArrayToGraphQlData((array)$value);
                                    } else {
                                        $testData[] = $key . ':' . $value;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return '{' . implode($separate, $testData) . '}';
    }

    protected function login(string $email, string $password, string $type = 'password', array $headers = []): array
    {
        $query = sprintf(
            '
            mutation {
                login(
                    credentials: {authType: %s, email: "%s", password: "%s" }
                ) {
                    __typename
                    ... on AuthenticationResult {
                        token
                        user {
                            id
                            email
                            first_name
                            last_name
                            phone
                            customer_number
                            created_at
                            last_login
                            social_connections
                        }
                    }
                    ... on AuthenticationResultRegistrationNeeded {
                        first_name
                        last_name
                        email
                        phone
                    }
                    ... on AuthenticationResultAlreadyRegistered {
                        first_name
                        last_name
                        email
                        phone
                    }
                }
            }
        ',
            $type,
            $email,
            addcslashes($password, '"\\/')
        );

        return $this->graphql($query, $headers)->json();
    }

    public function graphql(string $query, array $headers = [], array $variables = [])
    {
        $data = [
            'query' => $query
        ];

        if (count($variables)) {
            $data['variables'] = $variables;
        }

        return $this->postJson(
            '/graphql',
            $data,
            $headers
        );
    }

    protected function registerUser(
        string $first_name,
        string $last_name,
        string $email,
        ?string $password,
        string $authType = LoginService::PASSWORD_TYPE,
        ?string $token = null,
    ): array {
        $query = sprintf(
            'mutation {
            registration(
                registrationData: { first_name: "%s", last_name: "%s", email: "%s", auth_type: %s %s %s}
            ) {
                  token
                  user {
                    id
                    email
                    first_name
                    last_name
                    phone
                    customer_number
                    created_at
                    last_login
                    social_connections
                  }
              }
            }
        ',
            $first_name,
            $last_name,
            $email,
            $authType,
            $token ? sprintf('token: "%s"', $token) : '',
            $password? sprintf('password: "%s"', addcslashes($password, '"\\/')) : '',
        );

        return $this->graphql($query)->json();
    }

    protected function createAdmin(string $name, string $email): array
    {
        $query = sprintf(
            'mutation {
            createAdmin(
                createData: {name: "%s", email: "%s" }
            ) {
                id
                name
                email
                is_active
                created_at
              }
            }
        ',
            $name,
            $email
        );

        return $this->graphql($query)->json();
    }

    protected function bfAdminLogin(): array
    {
        $this->seed(BFAdminSeeder::class);
        $response = $this->adminLogin(BFAdminSeeder::ADMIN_EMAIL, BFAdminSeeder::ADMIN_PASSWORD);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('adminLogin', $response['data']);
        $loginData = $response['data']['adminLogin'];
        $this->assertNotEmpty($loginData['token']);
        $this->assertEquals(BFAdminSeeder::ADMIN_EMAIL, $loginData['admin']['email']);

        return $response;
    }

    protected function adminLogin(string $email, string $password): array
    {
        $query = sprintf(
            'mutation {
                      adminLogin(
                        credentials: {email: "%s", password: "%s" }
                      ) {
                          token
                          admin {
                            id
                            email
                            name
                            is_active
                            created_at
                            updated_at
                          }
                      }
                    }
        ',
            $email,
            addcslashes($password, '"\\/')
        );

        return $this->graphql($query)->json();
    }

    protected function getAuthHeader(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    protected function getTestFileContent(string $path): string
    {
        return trim(Storage::disk('test')->get('/erp/' . $path));
    }

    protected function getToken(string $email, string $password = 'testing1', string $guard = 'user'): string
    {
        /** @var string|null $token */
        $token = Auth::guard($guard)->attempt(
            [
                'email' => $email,
                'password' => $password,
            ]
        );
        return $token;
    }

    protected function setupPriceLists()
    {
        $this->singlePriceList = ProductPriceList::factory()->create([
            'mac_title' => config('erp.synced_price_lists.single.mac_enum'),
            'mac_ordinal' => config('erp.synced_price_lists.single.mac_ordinal'),
            'mac_issue' => 1,
        ]);
        $this->clubPriceList = ProductPriceList::factory()->create([
            'mac_title' => config('erp.synced_price_lists.club.mac_enum'),
            'mac_ordinal' => config('erp.synced_price_lists.club.mac_ordinal'),
            'mac_issue' => 1,
        ]);
    }

    protected function setupCustomerGroups()
    {
        $this->clubCustomerGroup = CustomerGroup::factory()->create([
            'mac_title' => 'Bortársaság Klub',
            'mac_title_value' => config('erp.synced_customer_groups.club_customer.mac_enum'),
            'mac_ordinal' => config('erp.synced_customer_groups.club_customer.mac_ordinal'),
        ]);
        $this->specialCustomerGroup = CustomerGroup::factory()->create([
            'mac_title_value' => config('erp.synced_customer_groups.special_customer.mac_enum'),
            'mac_ordinal' => config('erp.synced_customer_groups.special_customer.mac_ordinal'),
        ]);
        $this->shopCustomerGroup = CustomerGroup::factory()->create([
            'mac_title_value' => config('erp.synced_customer_groups.shop_customer.mac_enum'),
            'mac_ordinal' => config('erp.synced_customer_groups.shop_customer.mac_ordinal'),
        ]);
    }

    protected function createProductStock(
        Product $product,
        int     $quantity = 999,
        string  $warehouseMacTitle = 'kőérberki_raktár',
        int     $backorderQuantity = 0,
        bool    $resetStock = false,
    ): void
    {
        /** @var ProductWarehouse|null $warehouse */
        $warehouse = ProductWarehouse::query()->where('mac_title_value',$warehouseMacTitle)->first();

        if (!$warehouse) {
            $warehouse = ProductWarehouse::factory([
                'mac_title_value' => $warehouseMacTitle,
            ])->create();
        }

        if ($resetStock) {
            ProductStock::query()->where('product_id', $product->id)->forceDelete();
        }

        ProductStock::factory([
            'stock_available' => $quantity,
            'backorder_volume' => $backorderQuantity,
            'product_warehouse_mac_title_value' => $warehouseMacTitle,
            'mac_product_id' => $product->mac_code,
            'product_id' => $product->id,
            'product_warehouse_id' => $warehouse->id,
        ])->create();
    }

    protected function  createProductExpressStock(
        Product $product,
        int $quantity
    ): void
    {
        $this->createProductStock($product, $quantity, ProductStock::PRODUCT_EXPRESS_ORDERABLE_WAREHOUSES);
    }

    protected function  createProductBackOrderStock(
        Product $product,
        int $backorderQuantity
    ): void
    {
        $this->createProductStock($product, 0, 'depo_raktár', $backorderQuantity);
    }


    protected function createOrderableProduct(
        $attributes = [],
        $withStock = true,
        int $price = 1000,
        int $macDiscount = 0,
        int $customerGroupId = null,
        int $withExpressStock = 0,
        int $clubPrice = null,
        int $cart_max_limit = null,
        int $shop_stock_limit = null,
        int $volume = 1,
    ): Product {
        Product::disableSearchSyncing();
        $product = $this->createVisibleProduct(array_merge([
            'cart_max_limit' => $cart_max_limit,
            'shop_stock_limit' => $shop_stock_limit,
            'vat' => 10,
            'weight' => 1,
            'volume' => $volume,
        ], $attributes), [], $price, $clubPrice ?? (int)($price * 0.9));

        if ($withStock) {
            $this->createProductStock($product);
        }

        if ($withExpressStock > 0) {
            $this->createProductExpressStock($product, $withExpressStock);
        }

        if ($macDiscount > 0) {
            ProductMacDiscount::factory([
                'product_id' => $product->id,
                'customer_group_id' => $customerGroupId,
                'discount_amount' => $macDiscount,
                'active_from' => Carbon::now()->subDay(),
                'active_to' => Carbon::now()->addDay(),
            ])->create();
        }
        Product::makeAllSearchable();

        return $product;
    }

    protected function createVisibleProduct(array $attributes = [], array $i18nAttributes = [], ?int $singlePrice = 1000, ?int $clubPrice = 1000): Product
    {
        /** @var Product $product */
        $product = Product::factory($attributes)
            ->create();

        ProductProductPriceList::withoutEvents(function () use ($product, $singlePrice, $clubPrice) {
            if ($singlePrice !== null) {
                ProductProductPriceList::factory()->create([
                    'product_price_list_id' => $this->singlePriceList->id,
                    'issue' => 1,
                    'product_id' => $product->id,
                    'price' => $singlePrice,
                ]);
            }

            if ($clubPrice !== null) {
                ProductProductPriceList::factory()->create([
                    'product_price_list_id' => $this->clubPriceList->id,
                    'issue' => 1,
                    'product_id' => $product->id,
                    'price' => $clubPrice,
                ]);
            }
        });

        ProductI18n::withoutEvents(function () use ($product, $i18nAttributes) {
            $faker = Factory::create();
            ProductI18n::factory()->create([
                'product_id' => $product->id,
                'language' => Translation::LANG_HU,
                'title' => $i18nAttributes['titleHu'] ?? $faker->word(),
                'description' => $i18nAttributes['descriptionHu'] ?? $faker->sentence(),
                'wine_type' => $i18nAttributes['wineTypeHu'] ?? $faker->randomElement(['száraz', 'édes', 'félszáraz', 'félédes']),
                'slug' => $i18nAttributes['slugHu'] ?? $faker->unique()->slug(),
                'meta_description' => $i18nAttributes['metaDescriptionHu'] ?? $faker->words(5, true),
            ]);

            ProductI18n::factory([
                'product_id' => $product->id,
                'language' => Translation::LANG_EN,
                'title' => $i18nAttributes['titleEn'] ?? $faker->word(),
                'description' => $i18nAttributes['descriptionEn'] ?? $faker->sentence(),
                'wine_type' => $i18nAttributes['wineTypeEn'] ?? $faker->randomElement(['dry', 'sweet', 'semi-dry', 'semi-sweet']),
                'slug' => $i18nAttributes['slugEn'] ?? $faker->unique()->slug(),
                'meta_description' => $i18nAttributes['metaDescriptionEn'] ?? $faker->words(5, true),
            ])->create();
        });

        return $product;
    }

    #[ArrayShape(['categories' => "\App\Models\ProductCategory[]|\Illuminate\Support\Collection", 'countries' => "\App\Models\ProductCountry[]|\Illuminate\Support\Collection", 'regions' => "\App\Models\ProductRegion[]|\Illuminate\Support\Collection", 'types' => "\App\Models\ProductType[]|\Illuminate\Support\Collection", 'brands' => "\App\Models\ProductBrand[]|\Illuminate\Support\Collection", 'colors' => "\App\Models\ProductColor[]|\Illuminate\Support\Collection", 'units' => "\App\Models\ProductUnit[]|\Illuminate\Support\Collection", 'tags' => "\App\Models\ProductTag[]|\Illuminate\Support\Collection"])]
    protected function createProductParams(int $count = 2): array
    {
        $i18nFactorySequence = new Sequence(
            ['language' => Translation::LANG_HU],
            ['language' => Translation::LANG_EN],
        );
        /** @var \Illuminate\Support\Collection|ProductCategory[] $categories */
        $categories = ProductCategory::factory()
            ->has(
                ProductCategoryI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create();

        /** @var \Illuminate\Support\Collection|ProductCountry[] $countries */
        $countries = ProductCountry::factory()
            ->has(
                ProductCountryI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create(
                ['menu_position' => 10]
            );

        /** @var \Illuminate\Support\Collection|ProductRegion[] $regions */
        $regions = ProductRegion::factory()
            ->has(
                ProductRegionI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create();

        /** @var \Illuminate\Support\Collection|ProductType[] $types */
        $types = ProductType::factory()
            ->has(
                ProductTypeI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create();

        /** @var \Illuminate\Support\Collection|ProductBrand[] $brands */
        $brands = ProductBrand::factory()
            ->has(
                ProductBrandI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create();

        /** @var \Illuminate\Support\Collection|ProductColor[] $colors */
        $colors = ProductColor::factory()
            ->has(
                ProductColorI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create();

        /** @var \Illuminate\Support\Collection|ProductUnit[] $units */
        $units = ProductUnit::factory()->count($count)->create();
        /** @var \Illuminate\Support\Collection|ProductUnit[] $bottleUnit */
        $bottleUnit = ProductUnit::query()->where('mac_title_value', ProductUnit::BOTTLE_MAC_TITLE_VALUE)->first();
        if (!$bottleUnit) {
            $bottleUnit = ProductUnit::factory()->create([
                'mac_title_value' => ProductUnit::BOTTLE_MAC_TITLE_VALUE
            ]);
        }

        /** @var \Illuminate\Support\Collection|ProductTag[] $tags */
        $tags = ProductTag::factory()
            ->has(
                ProductTagI18n::factory()
                    ->count(2)
                    ->state($i18nFactorySequence),
                'i18ns'
            )
            ->count($count)
            ->create();

        return [
            'categories' => $categories,
            'countries' => $countries,
            'regions' => $regions,
            'types' => $types,
            'brands' => $brands,
            'colors' => $colors,
            'units' => $units,
            'tags' => $tags,
            'bottleUnit' => $bottleUnit,
        ];
    }

    protected static function checkCartItemsByProductIds(array $productIds, array $cartItems): void
    {
        self::assertCount(count($productIds), $cartItems);
        $i=0;
        foreach ($productIds as $productId) {
            self::assertEquals($productId, Arr::get(
                $cartItems,
                sprintf('%s.product.id', $i++)
            ));
        }
    }

    protected static function getEnumKey(EnumType $enumType, string $enumValue): ?string
    {
        /** @var EnumValueDefinition|null $result */
        $result = Arr::first($enumType->getValues(), fn(EnumValueDefinition $enumDef) => $enumDef->value === $enumValue);

        return $result ? $result->name : null;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearCache();
        app()->forgetInstances();
        gc_collect_cycles();
        //echo ' ' . round(memory_get_usage()/1000000, 0) . ' ' ;
    }

    protected function clearCache(): void
    {
        $reflectionProperty = new \ReflectionProperty(CustomerGroup::class, 'groups');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);

        $reflectionProperty = new \ReflectionProperty(CustomerGroup::class, 'shopCustomerGroup');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null);
    }
}

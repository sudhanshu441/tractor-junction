# Krishi Junction — Database Design & ERD

Target: **MySQL 8.0** (InnoDB, utf8mb4_unicode_ci), Laravel 11 migrations.
Conventions: snake_case plural table names, `id` BIGINT UNSIGNED AUTO_INCREMENT PK,
`created_at/updated_at`, `deleted_at` where soft-delete applies, FK named `<singular>_id`,
`status` as tinyint/enum, every user-facing entity has `slug` UNIQUE where routable.

Total: **94 tables** across 12 domains.

- [1. Identity & access](#1-identity--access)
- [2. Geography](#2-geography)
- [3. Catalogue core](#3-catalogue-core-products-brands-categories-specs)
- [4. Pricing & media](#4-pricing--media)
- [5. Used marketplace](#5-used-marketplace)
- [6. Inspection & valuation](#6-inspection--valuation)
- [7. Dealers](#7-dealers)
- [8. Leads & enquiries](#8-leads--enquiries)
- [9. Finance & insurance](#9-finance--insurance)
- [10. Reviews, compare, wishlist](#10-reviews-compare-wishlist)
- [11. CMS, SEO & offers](#11-cms-seo--offers)
- [12. System, notifications & analytics](#12-system-notifications--analytics)
- [13. Master relationship overview](#13-master-relationship-overview)
- [14. Key design decisions](#14-key-design-decisions)
- [15. Index & performance plan](#15-index--performance-plan)

---

## 1. Identity & access

```mermaid
erDiagram
    USERS ||--o| USER_PROFILES : has
    USERS ||--o{ USER_DEVICES : owns
    USERS ||--o{ OTP_VERIFICATIONS : requests
    USERS ||--o{ MODEL_HAS_ROLES : assigned
    ROLES ||--o{ MODEL_HAS_ROLES : in
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : grants
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : in
    USERS ||--o{ ACTIVITY_LOGS : performs
    USERS ||--o{ NOTIFICATIONS : receives
    USERS ||--o{ SAVED_SEARCHES : saves

    USERS {
        bigint id PK
        string name
        string mobile UK "10-digit, unique"
        timestamp mobile_verified_at
        string email UK "nullable"
        timestamp email_verified_at
        string password "nullable when OTP-only"
        enum user_type "customer|dealer|staff"
        bigint state_id FK
        bigint district_id FK
        bigint city_id FK
        string locale "en|hi"
        string avatar
        boolean is_active
        string blocked_reason
        timestamp last_login_at
        string last_login_ip
        string referral_code
        softdeletes deleted_at
    }
    USER_PROFILES {
        bigint id PK
        bigint user_id FK
        date date_of_birth
        enum gender
        decimal land_holding_acres
        string farming_type "irrigated|rain-fed|orchard|mixed"
        json crops
        string address_line
        string pincode
        json preferences "notification + language prefs"
    }
    USER_DEVICES {
        bigint id PK
        bigint user_id FK
        string device_uuid
        string platform "web|android|ios"
        string fcm_token
        string user_agent
        string ip
        timestamp last_active_at
    }
    OTP_VERIFICATIONS {
        bigint id PK
        string mobile
        string otp_hash
        enum purpose "login|register|listing|lead|loan"
        tinyint attempts
        timestamp expires_at
        timestamp verified_at
        string ip
    }
    SAVED_SEARCHES {
        bigint id PK
        bigint user_id FK
        string name
        enum context "new|used|dealer"
        json filters
        boolean alert_enabled
        timestamp last_alert_at
    }
```

Also from Spatie: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.
Laravel defaults: `password_reset_tokens`, `sessions`, `personal_access_tokens`, `jobs`, `failed_jobs`, `cache`.

---

## 2. Geography

```mermaid
erDiagram
    COUNTRIES ||--o{ STATES : has
    STATES ||--o{ DISTRICTS : has
    DISTRICTS ||--o{ CITIES : has
    CITIES ||--o{ PINCODES : has

    COUNTRIES { bigint id PK  string name  string iso2  string phone_code }
    STATES {
        bigint id PK
        bigint country_id FK
        string name
        string slug UK
        string code "RJ, UP…"
        decimal latitude
        decimal longitude
        boolean is_active
    }
    DISTRICTS {
        bigint id PK
        bigint state_id FK
        string name
        string slug
        decimal latitude
        decimal longitude
        boolean is_active
    }
    CITIES {
        bigint id PK
        bigint district_id FK
        bigint state_id FK "denormalised for fast filters"
        string name
        string slug
        decimal latitude
        decimal longitude
        boolean is_popular
    }
    PINCODES { bigint id PK  bigint city_id FK  string code  decimal latitude  decimal longitude }
```

---

## 3. Catalogue core (products, brands, categories, specs)

A single polymorphic product core serves tractors, implements, harvesters, tyres and
farm tools. `category_id` decides which spec attributes apply — so a new machinery type
needs **zero** schema change.

```mermaid
erDiagram
    BRANDS ||--o{ PRODUCTS : makes
    CATEGORIES ||--o{ CATEGORIES : parent_of
    CATEGORIES ||--o{ PRODUCTS : classifies
    BRANDS ||--o{ BRAND_CATEGORY : deals_in
    CATEGORIES ||--o{ BRAND_CATEGORY : of
    PRODUCTS ||--o{ PRODUCT_VARIANTS : has
    PRODUCTS ||--o{ PRODUCT_SPEC_VALUES : described_by
    PRODUCT_VARIANTS ||--o{ PRODUCT_SPEC_VALUES : overrides
    SPEC_GROUPS ||--o{ SPEC_ATTRIBUTES : contains
    SPEC_ATTRIBUTES ||--o{ PRODUCT_SPEC_VALUES : valued_in
    CATEGORIES ||--o{ CATEGORY_SPEC_ATTRIBUTE : applies
    SPEC_ATTRIBUTES ||--o{ CATEGORY_SPEC_ATTRIBUTE : applied_to
    PRODUCTS ||--o{ PRODUCT_FEATURES : highlights
    PRODUCTS ||--o{ PRODUCT_FAQS : answers
    PRODUCTS ||--o{ PRODUCT_COMPETITORS : compared_with

    BRANDS {
        bigint id PK
        string name
        string slug UK
        string logo
        text description
        string country
        year founded_year
        string website
        int sort_order
        boolean is_popular
        boolean is_active
    }
    CATEGORIES {
        bigint id PK
        bigint parent_id FK "nullable"
        enum type "tractor|implement|harvester|tyre|farm_tool"
        string name
        string slug UK
        string icon
        string banner
        text description
        int sort_order
        boolean is_active
    }
    PRODUCTS {
        bigint id PK
        bigint brand_id FK
        bigint category_id FK
        string name "e.g. 575 DI XP Plus"
        string slug UK
        string model_code
        enum status "available|upcoming|discontinued"
        decimal hp_min
        decimal hp_max
        decimal price_min "ex-showroom"
        decimal price_max
        year launch_year
        date expected_launch_date "for upcoming"
        text short_description
        longtext description
        json highlights
        string brochure_path
        decimal rating_avg "cached"
        int rating_count "cached"
        int view_count
        int popularity_score
        boolean is_featured
        boolean is_popular
        boolean is_active
        softdeletes deleted_at
    }
    PRODUCT_VARIANTS {
        bigint id PK
        bigint product_id FK
        string name "2WD / 4WD / Power steering"
        string slug
        decimal price
        boolean is_default
        boolean is_active
    }
    SPEC_GROUPS {
        bigint id PK
        string name "Engine, Transmission…"
        string slug
        int sort_order
        boolean is_active
    }
    SPEC_ATTRIBUTES {
        bigint id PK
        bigint spec_group_id FK
        string name "No. of Cylinders"
        string slug UK
        enum data_type "int|decimal|string|boolean|select|json"
        string unit "HP, cc, kg, mm, L"
        json options "for select"
        boolean is_filterable
        boolean is_comparable
        boolean is_key_spec "shown in the top summary"
        int sort_order
    }
    CATEGORY_SPEC_ATTRIBUTE {
        bigint id PK
        bigint category_id FK
        bigint spec_attribute_id FK
        boolean is_required
        int sort_order
    }
    PRODUCT_SPEC_VALUES {
        bigint id PK
        bigint product_id FK
        bigint product_variant_id FK "nullable = applies to all variants"
        bigint spec_attribute_id FK
        string value_string
        decimal value_number
        boolean value_boolean
        json value_json
    }
    PRODUCT_FEATURES { bigint id PK  bigint product_id FK  string title  text description  string icon  int sort_order }
    PRODUCT_FAQS { bigint id PK  bigint product_id FK  string question  text answer  int sort_order  boolean is_active }
    PRODUCT_COMPETITORS { bigint id PK  bigint product_id FK  bigint competitor_product_id FK  int sort_order }
```

**Implement↔tractor compatibility** is derived from spec values
(`required_hp_min`, `required_hp_max`, `hitch_type`) — no extra table needed, with an
optional cache table `product_compatibilities (product_id, compatible_product_id)`
refreshed by a nightly job.

---

## 4. Pricing & media

```mermaid
erDiagram
    PRODUCTS ||--o{ PRODUCT_PRICES : priced_in
    PRODUCT_VARIANTS ||--o{ PRODUCT_PRICES : priced_in
    STATES ||--o{ PRODUCT_PRICES : for_state
    CITIES ||--o{ PRODUCT_PRICES : for_city
    PRODUCTS ||--o{ PRODUCT_PRICE_HISTORY : logs
    PRODUCTS ||--o{ MEDIA : illustrated_by
    PRODUCTS ||--o{ PRODUCT_VIDEOS : shown_in

    PRODUCT_PRICES {
        bigint id PK
        bigint product_id FK
        bigint product_variant_id FK "nullable"
        bigint state_id FK "nullable = national"
        bigint city_id FK "nullable"
        decimal ex_showroom
        decimal rto_charges
        decimal insurance_amount
        decimal other_charges
        decimal on_road_price "generated/stored"
        date effective_from
        date effective_to
        boolean is_active
    }
    PRODUCT_PRICE_HISTORY {
        bigint id PK
        bigint product_id FK
        bigint state_id FK
        decimal old_price
        decimal new_price
        bigint changed_by FK
        timestamp changed_at
    }
    MEDIA {
        bigint id PK
        string model_type "polymorphic: Product, UsedListing, Dealer, Blog…"
        bigint model_id
        string collection "gallery|primary|logo|documents|360"
        string file_name
        string path
        string disk "public|s3|private"
        string mime_type
        bigint size
        json conversions "thumb, medium, webp"
        string alt_text
        string caption
        int sort_order
    }
    PRODUCT_VIDEOS {
        bigint id PK
        bigint product_id FK
        string title
        string youtube_id
        string thumbnail
        enum type "review|walkaround|comparison|demo"
        int duration_seconds
        int view_count
        int sort_order
    }
```

---

## 5. Used marketplace

```mermaid
erDiagram
    USERS ||--o{ USED_LISTINGS : posts
    DEALERS ||--o{ USED_LISTINGS : posts
    PRODUCTS ||--o{ USED_LISTINGS : based_on
    CATEGORIES ||--o{ USED_LISTINGS : of
    BRANDS ||--o{ USED_LISTINGS : of
    USED_LISTINGS ||--o{ USED_LISTING_IMAGES : has
    USED_LISTINGS ||--o{ USED_LISTING_STATUS_LOGS : audited_by
    USED_LISTINGS ||--o{ LEADS : generates
    USED_LISTINGS ||--o| INSPECTIONS : verified_by
    USED_LISTINGS ||--o{ LISTING_REPORTS : flagged_by
    USED_LISTINGS ||--o{ LISTING_BOOSTS : promoted_by

    USED_LISTINGS {
        bigint id PK
        string reference_no UK "KJ-U-000123"
        bigint user_id FK "seller, nullable if dealer"
        bigint dealer_id FK "nullable"
        enum seller_type "owner|dealer|broker"
        bigint category_id FK
        bigint brand_id FK
        bigint product_id FK "matched catalogue model, nullable"
        string title
        string slug UK
        year manufacturing_year
        int engine_hours
        int hours_driven_estimated
        decimal hp
        enum condition "excellent|good|average|needs_repair"
        enum tyre_condition_front
        enum tyre_condition_rear
        boolean has_rc
        boolean has_insurance
        date insurance_valid_till
        boolean is_financed
        string registration_number "masked in public"
        decimal expected_price
        decimal negotiable_to
        boolean is_price_negotiable
        text description
        bigint state_id FK
        bigint district_id FK
        bigint city_id FK
        string pincode
        decimal latitude
        decimal longitude
        enum status "draft|pending|approved|live|sold|expired|rejected|blocked"
        text rejection_reason
        boolean is_verified "inspection passed"
        boolean is_featured
        timestamp featured_till
        int view_count
        int lead_count
        timestamp published_at
        timestamp expires_at
        timestamp sold_at
        bigint approved_by FK
        softdeletes deleted_at
    }
    USED_LISTING_IMAGES {
        bigint id PK
        bigint used_listing_id FK
        string path
        string thumbnail_path
        enum angle "front|rear|left|right|engine|tyre|meter|document|other"
        boolean is_primary
        string image_hash "duplicate detection"
        int sort_order
    }
    USED_LISTING_STATUS_LOGS {
        bigint id PK
        bigint used_listing_id FK
        string from_status
        string to_status
        bigint changed_by FK
        text remarks
        timestamp created_at
    }
    LISTING_REPORTS {
        bigint id PK
        bigint used_listing_id FK
        bigint reported_by FK "nullable"
        enum reason "sold|fake|wrong_price|spam|abusive|duplicate|other"
        text details
        enum status "open|reviewed|actioned|dismissed"
        bigint reviewed_by FK
    }
    LISTING_BOOSTS {
        bigint id PK
        bigint used_listing_id FK
        bigint plan_id FK
        decimal amount
        bigint payment_id FK
        timestamp starts_at
        timestamp ends_at
        enum status "active|expired|cancelled"
    }
```

---

## 6. Inspection & valuation

```mermaid
erDiagram
    USED_LISTINGS ||--o| INSPECTIONS : has
    USERS ||--o{ INSPECTIONS : inspects
    INSPECTIONS ||--o{ INSPECTION_ITEMS : scores
    INSPECTION_CHECKLIST_ITEMS ||--o{ INSPECTION_ITEMS : template_for
    PRODUCTS ||--o{ VALUATION_RULES : depreciates_by

    INSPECTIONS {
        bigint id PK
        bigint used_listing_id FK
        bigint inspector_id FK
        string reference_no UK
        date scheduled_at
        timestamp completed_at
        enum status "requested|scheduled|in_progress|completed|cancelled"
        decimal overall_score "0-100"
        enum grade "A|B|C|D"
        decimal valuation_min
        decimal valuation_max
        text summary
        string report_pdf_path
        bigint approved_by FK
    }
    INSPECTION_CHECKLIST_ITEMS {
        bigint id PK
        string section "Engine|Transmission|Hydraulics|Tyres|Body|Documents|Electricals"
        string name
        decimal weight
        int sort_order
        boolean is_active
    }
    INSPECTION_ITEMS {
        bigint id PK
        bigint inspection_id FK
        bigint checklist_item_id FK
        tinyint score "0-10"
        text remarks
        string photo_path
    }
    VALUATION_RULES {
        bigint id PK
        bigint category_id FK
        enum factor_type "age|hours|condition|region|brand"
        string key "year_1, 0-1000hrs, grade_A, RJ…"
        decimal multiplier
        boolean is_active
    }
```

---

## 7. Dealers

```mermaid
erDiagram
    USERS ||--o{ DEALER_USERS : staffs
    DEALERS ||--o{ DEALER_USERS : employs
    DEALERS ||--o{ DEALER_BRANCHES : operates
    DEALERS ||--o{ DEALER_BRAND : authorised_for
    BRANDS ||--o{ DEALER_BRAND : authorises
    DEALERS ||--o{ DEALER_INVENTORY : stocks
    PRODUCTS ||--o{ DEALER_INVENTORY : stocked_as
    DEALERS ||--o{ DEALER_DOCUMENTS : submits
    DEALERS ||--o{ DEALER_SUBSCRIPTIONS : subscribes
    PLANS ||--o{ DEALER_SUBSCRIPTIONS : sold_as
    DEALERS ||--o{ LEADS : receives
    DEALERS ||--o{ REVIEWS : rated_in

    DEALERS {
        bigint id PK
        string code UK "KJ-D-00123"
        bigint owner_user_id FK
        string business_name
        string display_name
        string slug UK
        enum dealer_type "authorised|multi_brand|used_only|implement"
        string gstin
        string pan
        string contact_person
        string mobile
        string alternate_mobile
        string email
        text address
        bigint state_id FK
        bigint district_id FK
        bigint city_id FK
        string pincode
        decimal latitude
        decimal longitude
        string logo
        text about
        json working_hours
        enum verification_status "pending|verified|rejected|suspended"
        text verification_remarks
        bigint verified_by FK
        timestamp verified_at
        decimal rating_avg
        int rating_count
        int lead_count
        boolean is_featured
        boolean is_active
        softdeletes deleted_at
    }
    DEALER_BRANCHES {
        bigint id PK
        bigint dealer_id FK
        string name
        text address
        bigint state_id FK
        bigint district_id FK
        bigint city_id FK
        string pincode
        string mobile
        decimal latitude
        decimal longitude
        boolean is_head_office
        boolean is_active
    }
    DEALER_USERS { bigint id PK  bigint dealer_id FK  bigint user_id FK  enum role "owner|manager|sales"  boolean is_active }
    DEALER_BRAND { bigint id PK  bigint dealer_id FK  bigint brand_id FK  date authorised_since }
    DEALER_INVENTORY {
        bigint id PK
        bigint dealer_id FK
        bigint branch_id FK
        bigint product_id FK
        bigint product_variant_id FK
        int quantity
        decimal offer_price
        enum availability "in_stock|out_of_stock|on_order"
        text notes
        boolean is_active
    }
    DEALER_DOCUMENTS {
        bigint id PK
        bigint dealer_id FK
        enum doc_type "gst|pan|shop_licence|authorisation_letter|photo|other"
        string file_path "private disk"
        enum status "pending|approved|rejected"
        text remarks
        timestamp expires_at
    }
    PLANS {
        bigint id PK
        string name "Free|Silver|Gold"
        enum audience "dealer|seller"
        decimal price
        enum billing_cycle "monthly|quarterly|yearly|one_time"
        int lead_limit
        int inventory_limit
        int featured_listing_count
        json features
        boolean is_active
    }
    DEALER_SUBSCRIPTIONS {
        bigint id PK
        bigint dealer_id FK
        bigint plan_id FK
        date starts_at
        date ends_at
        int leads_consumed
        enum status "active|expired|cancelled|pending_payment"
        bigint payment_id FK
    }
```

---

## 8. Leads & enquiries

One polymorphic lead table is the spine of the business.

```mermaid
erDiagram
    LEADS ||--o{ LEAD_ACTIVITIES : timeline
    LEADS ||--o{ LEAD_ASSIGNMENTS : routed_by
    USERS ||--o{ LEADS : submits
    USERS ||--o{ LEAD_ASSIGNMENTS : assigned_to
    DEALERS ||--o{ LEAD_ASSIGNMENTS : assigned_to
    LEAD_SOURCES ||--o{ LEADS : came_from

    LEADS {
        bigint id PK
        string reference_no UK "KJ-L-000456"
        enum type "new_product|used_listing|dealer|loan|insurance|callback|offer|contact|sell_request"
        string leadable_type "polymorphic: Product|UsedListing|Dealer|Offer|LoanApplication"
        bigint leadable_id
        bigint user_id FK "nullable for guests"
        string name
        string mobile
        boolean mobile_verified
        string email
        bigint state_id FK
        bigint district_id FK
        bigint city_id FK
        text message
        json meta "budget, timeline, financing_needed, utm_*"
        bigint source_id FK
        string channel "web|mobile_web|api|app|phone|whatsapp"
        enum status "new|assigned|contacted|qualified|converted|lost|duplicate|invalid"
        enum quality "hot|warm|cold"
        string lost_reason
        bigint duplicate_of_id FK
        date next_follow_up_at
        timestamp first_contacted_at
        timestamp converted_at
        string ip
        string user_agent
        softdeletes deleted_at
    }
    LEAD_ASSIGNMENTS {
        bigint id PK
        bigint lead_id FK
        enum assignee_type "dealer|staff"
        bigint dealer_id FK
        bigint user_id FK
        bigint assigned_by FK
        enum status "pending|accepted|rejected|expired"
        timestamp assigned_at
        timestamp responded_at
        text remarks
    }
    LEAD_ACTIVITIES {
        bigint id PK
        bigint lead_id FK
        bigint user_id FK
        enum activity "note|call|sms|whatsapp|email|status_change|assignment|visit"
        string from_status
        string to_status
        text description
        int call_duration_seconds
        string call_recording_url
        timestamp created_at
    }
    LEAD_SOURCES { bigint id PK  string name "organic|google_ads|facebook|referral|direct|app"  string utm_source  boolean is_active }
```

---

## 9. Finance & insurance

```mermaid
erDiagram
    USERS ||--o{ LOAN_APPLICATIONS : applies
    PRODUCTS ||--o{ LOAN_APPLICATIONS : financed
    USED_LISTINGS ||--o{ LOAN_APPLICATIONS : financed
    LOAN_APPLICATIONS ||--o{ LOAN_DOCUMENTS : supported_by
    LOAN_APPLICATIONS ||--o{ LOAN_APPLICATION_LENDERS : sent_to
    LENDERS ||--o{ LOAN_APPLICATION_LENDERS : receives
    LOAN_APPLICATIONS ||--o{ LOAN_STATUS_LOGS : audited
    INSURANCE_PARTNERS ||--o{ INSURANCE_ENQUIRIES : receives
    USERS ||--o{ EMI_CALCULATIONS : runs

    LENDERS {
        bigint id PK
        string name
        string slug UK
        string logo
        enum lender_type "bank|nbfc|coop"
        decimal interest_min
        decimal interest_max
        int tenure_min_months
        int tenure_max_months
        decimal amount_min
        decimal amount_max
        decimal processing_fee_percent
        decimal max_ltv_percent
        json states_served
        text description
        boolean is_active
    }
    LOAN_APPLICATIONS {
        bigint id PK
        string reference_no UK "KJ-LN-00789"
        bigint user_id FK
        enum purpose "new_purchase|used_purchase|refinance"
        string financeable_type "Product|UsedListing"
        bigint financeable_id
        string applicant_name
        string mobile
        string email
        date date_of_birth
        string pan_masked
        string aadhaar_masked
        decimal annual_income
        enum income_source "farming|business|salary|other"
        decimal land_holding_acres
        decimal machinery_price
        decimal down_payment
        decimal loan_amount
        int tenure_months
        decimal expected_interest_rate
        decimal calculated_emi
        decimal cibil_score
        bigint state_id FK
        bigint district_id FK
        bigint city_id FK
        text address
        enum status "draft|submitted|under_review|docs_pending|sent_to_lender|sanctioned|rejected|disbursed|cancelled"
        text remarks
        bigint assigned_to FK
        timestamp submitted_at
        timestamp decided_at
        softdeletes deleted_at
    }
    LOAN_DOCUMENTS {
        bigint id PK
        bigint loan_application_id FK
        enum doc_type "aadhaar|pan|land_record|bank_statement|income_proof|photo|quotation|rc|other"
        string file_path "private disk only"
        string original_name
        enum status "pending|verified|rejected"
        text remarks
        bigint verified_by FK
    }
    LOAN_APPLICATION_LENDERS {
        bigint id PK
        bigint loan_application_id FK
        bigint lender_id FK
        timestamp sent_at
        enum status "sent|acknowledged|sanctioned|rejected"
        decimal sanctioned_amount
        decimal offered_rate
        text remarks
    }
    LOAN_STATUS_LOGS { bigint id PK  bigint loan_application_id FK  string from_status  string to_status  bigint changed_by FK  text remarks  timestamp created_at }
    INSURANCE_PARTNERS {
        bigint id PK
        string name
        string slug
        string logo
        json coverage_types
        text description
        json states_served
        boolean is_active
    }
    INSURANCE_ENQUIRIES {
        bigint id PK
        string reference_no UK
        bigint user_id FK
        bigint insurance_partner_id FK
        bigint product_id FK
        string registration_number
        year manufacturing_year
        enum coverage_type "comprehensive|third_party|own_damage"
        date previous_policy_expiry
        boolean has_claim_history
        decimal idv_expected
        enum status "new|contacted|quoted|converted|lost"
        bigint assigned_to FK
    }
    EMI_CALCULATIONS {
        bigint id PK
        bigint user_id FK "nullable"
        bigint product_id FK "nullable"
        decimal price
        decimal down_payment
        decimal interest_rate
        int tenure_months
        enum frequency "monthly|quarterly|half_yearly|yearly"
        decimal emi
        decimal total_interest
        decimal total_payable
        string ip
        timestamp created_at
    }
```

---

## 10. Reviews, compare, wishlist

```mermaid
erDiagram
    USERS ||--o{ REVIEWS : writes
    REVIEWS ||--o{ REVIEW_RATINGS : detailed_by
    REVIEWS ||--o{ REVIEW_VOTES : voted_on
    REVIEWS ||--o| REVIEW_REPLIES : answered_by
    USERS ||--o{ WISHLISTS : saves
    USERS ||--o{ COMPARISONS : builds
    COMPARISONS ||--o{ COMPARISON_ITEMS : contains

    REVIEWS {
        bigint id PK
        string reviewable_type "Product|Dealer|UsedListing"
        bigint reviewable_id
        bigint user_id FK
        string title
        text body
        tinyint rating "1-5"
        string ownership_duration "<6m|6-12m|1-3y|3y+"
        text pros
        text cons
        boolean is_verified_owner
        enum status "pending|approved|rejected"
        text rejection_reason
        int helpful_count
        int unhelpful_count
        bigint moderated_by FK
        softdeletes deleted_at
    }
    REVIEW_RATINGS { bigint id PK  bigint review_id FK  enum aspect "mileage|comfort|maintenance|performance|value|service"  tinyint rating }
    REVIEW_VOTES { bigint id PK  bigint review_id FK  bigint user_id FK  boolean is_helpful }
    REVIEW_REPLIES { bigint id PK  bigint review_id FK  bigint user_id FK  text body  enum status }
    WISHLISTS { bigint id PK  bigint user_id FK  string wishable_type "Product|UsedListing|Dealer"  bigint wishable_id  timestamp created_at }
    COMPARISONS { bigint id PK  bigint user_id FK "nullable"  string session_id  string slug UK "shareable"  bigint category_id FK  timestamp created_at }
    COMPARISON_ITEMS { bigint id PK  bigint comparison_id FK  bigint product_id FK  int position }
```

---

## 11. CMS, SEO & offers

```mermaid
erDiagram
    BLOG_CATEGORIES ||--o{ BLOGS : classifies
    USERS ||--o{ BLOGS : authors
    BLOGS ||--o{ BLOG_TAG : tagged
    TAGS ||--o{ BLOG_TAG : tags
    BLOGS ||--o{ BLOG_COMMENTS : discussed_in
    PAGES ||--o{ SEO_META : optimised
    PRODUCTS ||--o{ SEO_META : optimised
    OFFERS ||--o{ OFFER_PRODUCT : applies_to
    PRODUCTS ||--o{ OFFER_PRODUCT : discounted_in
    MENUS ||--o{ MENU_ITEMS : contains

    PAGES {
        bigint id PK
        string title
        string slug UK
        longtext content
        string template "default|contact|about|faq"
        boolean is_active
        boolean show_in_footer
        int sort_order
    }
    BLOG_CATEGORIES { bigint id PK  string name  string slug UK  string icon  int sort_order  boolean is_active }
    BLOGS {
        bigint id PK
        bigint blog_category_id FK
        bigint author_id FK
        enum type "blog|news|guide|press"
        string title
        string slug UK
        text excerpt
        longtext content
        string cover_image
        int reading_minutes
        int view_count
        boolean is_featured
        enum status "draft|scheduled|published|archived"
        timestamp published_at
        softdeletes deleted_at
    }
    TAGS { bigint id PK  string name  string slug UK }
    BLOG_TAG { bigint id PK  bigint blog_id FK  bigint tag_id FK }
    BLOG_COMMENTS { bigint id PK  bigint blog_id FK  bigint user_id FK  bigint parent_id FK  text body  enum status }
    VIDEOS {
        bigint id PK
        string title
        string slug UK
        string youtube_id
        string thumbnail
        bigint category_id FK
        bigint product_id FK "nullable"
        text description
        int duration_seconds
        int view_count
        boolean is_featured
        boolean is_active
    }
    FAQS { bigint id PK  string category "general|buying|selling|loan|dealer"  string question  text answer  int sort_order  boolean is_active }
    BANNERS {
        bigint id PK
        enum position "home_slider|home_mid|listing_top|sidebar|detail_bottom|app_promo"
        string title
        string subtitle
        string image_desktop
        string image_mobile
        string cta_text
        string cta_url
        enum device "all|desktop|mobile"
        date starts_at
        date ends_at
        int click_count
        int impression_count
        int sort_order
        boolean is_active
    }
    TESTIMONIALS { bigint id PK  string name  string designation  string city  string photo  text message  tinyint rating  boolean is_active  int sort_order }
    OFFERS {
        bigint id PK
        string title
        string slug UK
        text description
        bigint brand_id FK "nullable"
        enum discount_type "flat|percent|cashback|exchange|freebie"
        decimal discount_value
        string banner_image
        date starts_at
        date ends_at
        json states
        text terms
        int view_count
        boolean is_active
    }
    OFFER_PRODUCT { bigint id PK  bigint offer_id FK  bigint product_id FK }
    SEO_META {
        bigint id PK
        string seoable_type "Product|Page|Blog|Category|Brand|Dealer|UsedListing"
        bigint seoable_id
        string locale
        string meta_title
        text meta_description
        string meta_keywords
        string canonical_url
        string og_title
        text og_description
        string og_image
        string robots "index,follow"
        json schema_json
    }
    REDIRECTS { bigint id PK  string from_url UK  string to_url  smallint status_code "301|302"  int hit_count  boolean is_active }
    NOT_FOUND_LOGS { bigint id PK  string url  string referer  int hit_count  timestamp last_seen_at }
    MENUS { bigint id PK  string name "header|footer_1|footer_2|mobile"  string slug UK }
    MENU_ITEMS { bigint id PK  bigint menu_id FK  bigint parent_id FK  string label  string url  string icon  int sort_order  boolean is_active }
    NEWSLETTER_SUBSCRIBERS { bigint id PK  string email UK  string mobile  boolean is_active  timestamp unsubscribed_at }
    CONTACT_MESSAGES { bigint id PK  string name  string mobile  string email  string subject  text message  enum status "new|read|replied|closed"  bigint handled_by FK }
```

**Translations** — `translations (id, translatable_type, translatable_id, locale, field, value)`
covers content fields; UI strings live in `lang/{en,hi}/*.php` plus an admin-editable
`language_lines` table.

---

## 12. System, notifications & analytics

```mermaid
erDiagram
    USERS ||--o{ ACTIVITY_LOGS : acts
    USERS ||--o{ NOTIFICATIONS : gets
    NOTIFICATION_TEMPLATES ||--o{ NOTIFICATION_LOGS : rendered_as

    SETTINGS { bigint id PK  string group  string key UK  text value  enum type "string|int|bool|json|file"  boolean is_public }
    ACTIVITY_LOGS {
        bigint id PK
        bigint user_id FK
        string log_name
        string description
        string subject_type
        bigint subject_id
        json properties "old + new"
        string ip
        string user_agent
        timestamp created_at
    }
    NOTIFICATION_TEMPLATES {
        bigint id PK
        string event_key UK "lead.created, listing.approved…"
        string name
        json channels "sms,email,push,whatsapp,database"
        string sms_body
        string email_subject
        longtext email_body
        string push_title
        string push_body
        string whatsapp_template_id
        json variables
        boolean is_active
    }
    NOTIFICATION_LOGS {
        bigint id PK
        bigint template_id FK
        bigint user_id FK
        string channel
        string recipient
        text payload
        enum status "queued|sent|failed|delivered"
        text error
        timestamp sent_at
    }
    NOTIFICATIONS { uuid id PK  string type  string notifiable_type  bigint notifiable_id  json data  timestamp read_at }
    PAGE_VIEWS { bigint id PK  string viewable_type  bigint viewable_id  bigint user_id FK  string session_id  string ip  string referer  string utm_source  date viewed_on }
    SEARCH_LOGS { bigint id PK  string term  string context  int results_count  bigint user_id FK  string ip  timestamp created_at }
    PAYMENTS {
        bigint id PK
        string reference_no UK
        bigint user_id FK
        string payable_type "DealerSubscription|ListingBoost"
        bigint payable_id
        decimal amount
        string currency
        string gateway "razorpay"
        string gateway_order_id
        string gateway_payment_id
        enum status "created|paid|failed|refunded"
        json gateway_response
    }
```

---

## 13. Master relationship overview

```mermaid
graph TD
    subgraph Identity
      U[users] --> UP[user_profiles]
      U --> R[roles/permissions]
    end
    subgraph Catalogue
      B[brands] --> P[products]
      C[categories] --> P
      P --> PV[product_variants]
      P --> PSV[product_spec_values]
      SA[spec_attributes] --> PSV
      SG[spec_groups] --> SA
      P --> PP[product_prices]
      P --> M[media]
    end
    subgraph Marketplace
      U --> UL[used_listings]
      P -.matched.-> UL
      UL --> ULI[used_listing_images]
      UL --> INS[inspections]
    end
    subgraph Network
      D[dealers] --> DB[dealer_branches]
      D --> DI[dealer_inventory]
      P --> DI
      D --> DS[dealer_subscriptions]
      PL[plans] --> DS
    end
    subgraph Demand
      L[leads]
      P --> L
      UL --> L
      D --> L
      L --> LA[lead_assignments]
      L --> LAC[lead_activities]
      D --> LA
      U --> LA
    end
    subgraph Finance
      U --> LN[loan_applications]
      P --> LN
      UL --> LN
      LN --> LD[loan_documents]
      LN --> LAL[loan_application_lenders]
      LE[lenders] --> LAL
    end
    subgraph Content
      BL[blogs] --> SEO[seo_meta]
      P --> SEO
      PG[pages] --> SEO
      OF[offers] --> P
    end
    subgraph Geo
      ST[states] --> DT[districts] --> CT[cities]
    end
    CT -.-> UL
    CT -.-> D
    CT -.-> L
    ST -.-> PP
```

---

## 14. Key design decisions

| # | Decision | Rationale | Alternative rejected |
|---|---|---|---|
| D1 | **One `products` table + EAV specs** for tractors/implements/harvesters/tyres | New machinery types and new spec fields need no migration; admin adds attributes | Separate `tractors`, `implements`… tables → 5× duplicated CRUD & compare code |
| D2 | **Typed EAV columns** (`value_number`, `value_string`, `value_boolean`) rather than one text column | Numeric range filters (HP 40–50) stay index-able and fast | Single `value` varchar → no range queries |
| D3 | **Polymorphic `leads`** for all 9 enquiry types | One inbox, one routing engine, one report; new lead type = new enum value | Separate enquiry tables per feature |
| D4 | **`used_listings` separate from `products`** | Different lifecycle, ownership, moderation, expiry, and per-unit attributes | Reusing products with `is_used` flag → status/moderation pollution |
| D5 | **Price rows are geo + date scoped** with history | On-road price differs by state and changes often; SEO pages need "price in <city>" | Single price column on product |
| D6 | **Spatie permission tables**, single `users` table with `user_type` + guards | One identity, many panels; a dealer owner is still a user | Separate `admins`/`dealers` auth tables → duplicated auth code |
| D7 | **Polymorphic `media`, `seo_meta`, `reviews`, `wishlists`** | Uniform handling across all entity types | Per-entity image/SEO tables |
| D8 | **Cached aggregates** (`rating_avg`, `view_count`, `lead_count`) on parent rows | Listing pages must not COUNT() per row | Compute on read |
| D9 | **KYC/loan docs on a private disk** with signed URLs, PAN/Aadhaar stored masked | DPDP compliance; a public URL leak is unacceptable | Public storage |
| D10 | **Status enums + `*_status_logs` tables** for listings, leads, loans | Auditable, disputable state machines | Status column only |
| D11 | **`reference_no` human IDs** (KJ-L-000456) alongside PKs | Call-centre and WhatsApp support | Exposing raw incrementing IDs |
| D12 | **Soft deletes** on users, products, listings, leads, dealers, blogs | Business data is never truly deleted; audit + restore | Hard delete |

---

## 15. Index & performance plan

| Table | Indexes |
|---|---|
| `products` | `(brand_id, status)`, `(category_id, status)`, `slug` UK, `(is_popular, popularity_score)`, FULLTEXT(`name`) |
| `product_spec_values` | `(spec_attribute_id, value_number)`, `(product_id, spec_attribute_id)` UK-ish, `(spec_attribute_id, value_string(50))` |
| `product_prices` | `(product_id, state_id, is_active)`, `(state_id, effective_from)` |
| `used_listings` | `(status, published_at)`, `(state_id, district_id, status)`, `(brand_id, category_id, status)`, `(expected_price)`, `(manufacturing_year)`, `(latitude, longitude)`, `slug` UK, `(user_id, created_at)` |
| `leads` | `(type, status, created_at)`, `(leadable_type, leadable_id)`, `(mobile)`, `(district_id, status)`, `(next_follow_up_at)` |
| `lead_assignments` | `(dealer_id, status)`, `(user_id, status)`, `(lead_id)` |
| `dealers` | `(state_id, district_id, verification_status)`, `slug` UK, `(latitude, longitude)`, `(is_featured, rating_avg)` |
| `dealer_inventory` | `(dealer_id, product_id)` UK, `(product_id, availability)` |
| `loan_applications` | `(status, submitted_at)`, `(user_id)`, `(assigned_to, status)`, `reference_no` UK |
| `reviews` | `(reviewable_type, reviewable_id, status)`, `(user_id)` |
| `media` | `(model_type, model_id, collection)` |
| `seo_meta` | `(seoable_type, seoable_id, locale)` UK |
| `page_views` | `(viewable_type, viewable_id, viewed_on)` — partitioned/archived monthly |
| `activity_logs` | `(subject_type, subject_id)`, `(user_id, created_at)` — archived quarterly |

**Caching:** Redis for filter facets (15 min), home-page blocks (30 min), model detail
fragments (60 min), spec attribute definitions (24 h), geography (24 h). Cache tags
invalidated by model observers.

**Search:** Laravel Scout → Meilisearch indexes for `products`, `used_listings`,
`dealers`, `blogs` with synonyms (mahindra/महिंद्रा) and typo tolerance.

---

## 16. Seed data plan (phase 1)

| Table | Seeded rows |
|---|---|
| countries/states/districts/cities | 1 / 36 / ~780 / ~4 000 |
| brands | 25 |
| categories | ~45 (tractor 8, implement 25, harvester 4, tyre 3, farm tool 5) |
| spec_groups / spec_attributes | 12 / ~120 |
| products (demo) | ~60 tractors + ~40 implements |
| product_prices | national + 10 major states per product |
| lenders / insurance_partners | 12 / 6 |
| plans | 3 dealer + 2 seller |
| roles / permissions | 10 / ~180 |
| settings | ~60 keys |
| notification_templates | ~25 events |
| faqs / pages | 30 / 6 |

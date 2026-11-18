@php
  $publicContent = is_array($settings->home_sections) ? $settings->home_sections : [];
  $publicSteps = $publicContent['steps'] ?? [];
  $publicPanels = $publicContent['panel_cards'] ?? [];
  $publicBenefits = $publicContent['benefits'] ?? [];
  $publicFaq = $publicContent['faq_items'] ?? [];
  $contactFaq = $publicContent['contact_faq_items'] ?? [];
@endphp

<x-admin.legend title="صفحه اصلی؛ بنر و معرفی" />
<p class="adm-muted wide">عنوان خود تصویر از بخش «بنرها» و عنوان و توضیح همراه آن از این قسمت کنترل می‌شود. برای تصویر اصلی، در مدیریت بنر جایگاه «بنر اصلی صفحه خانه» را انتخاب کنید.</p>
<x-admin.input label="نشان بالای تیتر" name="home_sections[hero_badge]" :value="data_get($publicContent, 'hero_badge')" />
<x-admin.textarea label="توضیح اصلی بنر" name="home_sections[hero_description]" :value="data_get($publicContent, 'hero_description')" />
<x-admin.input label="متن دکمه اصلی" name="home_sections[hero_primary_label]" :value="data_get($publicContent, 'hero_primary_label')" />
<x-admin.input label="متن دکمه دوم" name="home_sections[hero_secondary_label]" :value="data_get($publicContent, 'hero_secondary_label')" />
<x-admin.input label="عنوان بالای دسته‌بندی‌ها" name="home_sections[categories_eyebrow]" :value="data_get($publicContent, 'categories_eyebrow')" />
<x-admin.input label="تیتر دسته‌بندی‌ها" name="home_sections[categories_title]" :value="data_get($publicContent, 'categories_title')" wide />

<x-admin.legend title="صفحه اصلی؛ دوره‌ها و آمار" />
<x-admin.input label="برچسب بخش جدیدترین دوره‌ها" name="home_sections[latest_courses_eyebrow]" :value="data_get($publicContent, 'latest_courses_eyebrow')" />
<x-admin.input label="تیتر جدیدترین دوره‌ها" name="home_sections[latest_courses_title]" :value="data_get($publicContent, 'latest_courses_title')" />
<x-admin.textarea label="توضیح جدیدترین دوره‌ها" name="home_sections[latest_courses_description]" :value="data_get($publicContent, 'latest_courses_description')" />
<x-admin.input label="برچسب بخش دوره‌های پرطرفدار" name="home_sections[popular_courses_eyebrow]" :value="data_get($publicContent, 'popular_courses_eyebrow')" />
<x-admin.input label="تیتر دوره‌های پرطرفدار" name="home_sections[popular_courses_title]" :value="data_get($publicContent, 'popular_courses_title')" />
<x-admin.textarea label="توضیح دوره‌های پرطرفدار" name="home_sections[popular_courses_description]" :value="data_get($publicContent, 'popular_courses_description')" wide />
@foreach (range(0, 3) as $statIndex)
  <x-admin.input label="عنوان شاخص شماره {{ $statIndex + 1 }}" name="home_sections[stats_labels][{{ $statIndex }}]" :value="$publicContent['stats_labels'][$statIndex] ?? ''" />
@endforeach

<x-admin.legend title="صفحه اصلی؛ مسیر یادگیری و پنل‌ها" />
<x-admin.input label="برچسب بخش مسیر یادگیری" name="home_sections[steps_eyebrow]" :value="data_get($publicContent, 'steps_eyebrow')" />
<x-admin.input label="تیتر مسیر یادگیری" name="home_sections[steps_title]" :value="data_get($publicContent, 'steps_title')" />
<x-admin.textarea label="توضیح مسیر یادگیری" name="home_sections[steps_description]" :value="data_get($publicContent, 'steps_description')" wide />
@foreach (range(0, 3) as $stepIndex)
  <x-admin.input label="عنوان گام {{ $stepIndex + 1 }}" name="home_sections[steps][{{ $stepIndex }}][title]" :value="$publicSteps[$stepIndex]['title'] ?? ''" />
  <x-admin.textarea label="توضیح گام {{ $stepIndex + 1 }}" name="home_sections[steps][{{ $stepIndex }}][body]" :value="$publicSteps[$stepIndex]['body'] ?? ''" />
@endforeach
<x-admin.input label="برچسب بخش پنل‌ها" name="home_sections[panels_eyebrow]" :value="data_get($publicContent, 'panels_eyebrow')" />
<x-admin.input label="تیتر پنل‌ها" name="home_sections[panels_title]" :value="data_get($publicContent, 'panels_title')" />
<x-admin.textarea label="توضیح پنل‌ها" name="home_sections[panels_description]" :value="data_get($publicContent, 'panels_description')" wide />
@foreach (range(0, 3) as $panelIndex)
  <x-admin.input label="عنوان کارت پنل {{ $panelIndex + 1 }}" name="home_sections[panel_cards][{{ $panelIndex }}][title]" :value="$publicPanels[$panelIndex]['title'] ?? ''" />
  <x-admin.textarea label="توضیح کارت پنل {{ $panelIndex + 1 }}" name="home_sections[panel_cards][{{ $panelIndex }}][body]" :value="$publicPanels[$panelIndex]['body'] ?? ''" />
@endforeach

<x-admin.legend title="صفحه اصلی؛ مزایا و نظرات" />
<x-admin.input label="برچسب بخش مزایا" name="home_sections[benefits_eyebrow]" :value="data_get($publicContent, 'benefits_eyebrow')" />
<x-admin.input label="تیتر مزایای آکادمی" name="home_sections[benefits_title]" :value="data_get($publicContent, 'benefits_title')" wide />
<x-admin.textarea label="توضیح مزایا" name="home_sections[benefits_description]" :value="data_get($publicContent, 'benefits_description')" wide />
<x-admin.input label="متن دکمه اول مزایا" name="home_sections[benefits_primary_label]" :value="data_get($publicContent, 'benefits_primary_label')" />
<x-admin.input label="متن دکمه دوم مزایا" name="home_sections[benefits_secondary_label]" :value="data_get($publicContent, 'benefits_secondary_label')" />
@foreach (range(0, 5) as $benefitIndex)
  <x-admin.input label="عنوان مزیت {{ $benefitIndex + 1 }}" name="home_sections[benefits][{{ $benefitIndex }}][title]" :value="$publicBenefits[$benefitIndex]['title'] ?? ''" />
  <x-admin.textarea label="توضیح مزیت {{ $benefitIndex + 1 }}" name="home_sections[benefits][{{ $benefitIndex }}][body]" :value="$publicBenefits[$benefitIndex]['body'] ?? ''" />
@endforeach
<x-admin.input label="برچسب بخش نظرات" name="home_sections[testimonials_eyebrow]" :value="data_get($publicContent, 'testimonials_eyebrow')" />
<x-admin.input label="تیتر بخش نظرات" name="home_sections[testimonials_title]" :value="data_get($publicContent, 'testimonials_title')" wide />

<x-admin.legend title="صفحه اصلی؛ مدرسان و مجله" />
<x-admin.input label="برچسب بخش مدرسان" name="home_sections[teachers_eyebrow]" :value="data_get($publicContent, 'teachers_eyebrow')" />
<x-admin.input label="تیتر مدرسان" name="home_sections[teachers_title]" :value="data_get($publicContent, 'teachers_title')" />
<x-admin.textarea label="توضیح مدرسان" name="home_sections[teachers_description]" :value="data_get($publicContent, 'teachers_description')" />
<x-admin.input label="برچسب بخش مقاله‌های صفحه اصلی" name="home_sections[articles_eyebrow]" :value="data_get($publicContent, 'articles_eyebrow')" />
<x-admin.input label="تیتر مقاله‌های صفحه اصلی" name="home_sections[articles_title]" :value="data_get($publicContent, 'articles_title')" />
<x-admin.textarea label="توضیح مقاله‌های صفحه اصلی" name="home_sections[articles_description]" :value="data_get($publicContent, 'articles_description')" wide />

<x-admin.legend title="صفحه اصلی؛ دعوت به اقدام و پرسش‌های متداول" />
<x-admin.input label="تیتر دعوت به اقدام" name="home_sections[cta_title]" :value="data_get($publicContent, 'cta_title')" wide />
<x-admin.textarea label="توضیح دعوت به اقدام" name="home_sections[cta_body]" :value="data_get($publicContent, 'cta_body')" wide />
<x-admin.input label="متن دکمه دعوت به اقدام" name="home_sections[cta_label]" :value="data_get($publicContent, 'cta_label')" />
<x-admin.input label="برچسب بخش پرسش‌های متداول" name="home_sections[faq_eyebrow]" :value="data_get($publicContent, 'faq_eyebrow')" />
<x-admin.input label="تیتر پرسش‌های متداول" name="home_sections[faq_title]" :value="data_get($publicContent, 'faq_title')" />
<x-admin.textarea label="توضیح پرسش‌های متداول" name="home_sections[faq_description]" :value="data_get($publicContent, 'faq_description')" />
<x-admin.input label="متن دکمه پرسش‌های متداول" name="home_sections[faq_cta_label]" :value="data_get($publicContent, 'faq_cta_label')" />
@foreach (range(0, 7) as $faqIndex)
  <x-admin.input label="پرسش متداول {{ $faqIndex + 1 }}" name="home_sections[faq_items][{{ $faqIndex }}][question]" :value="$publicFaq[$faqIndex]['question'] ?? ''" wide />
  <x-admin.textarea label="پاسخ پرسش {{ $faqIndex + 1 }}" name="home_sections[faq_items][{{ $faqIndex }}][answer]" :value="$publicFaq[$faqIndex]['answer'] ?? ''" wide />
@endforeach

<x-admin.legend title="صفحات عمومی؛ تماس، مدرسان و سبد خرید" />
<x-admin.input label="تیتر دعوت به همکاری مدرسان" name="home_sections[teachers_cta_title]" :value="data_get($publicContent, 'teachers_cta_title')" />
<x-admin.textarea label="توضیح دعوت به همکاری مدرسان" name="home_sections[teachers_cta_body]" :value="data_get($publicContent, 'teachers_cta_body')" />
<x-admin.input label="متن دکمه همکاری مدرسان" name="home_sections[teachers_cta_label]" :value="data_get($publicContent, 'teachers_cta_label')" />
<x-admin.input label="تیتر بخش پشتیبانی تماس" name="home_sections[contact_support_title]" :value="data_get($publicContent, 'contact_support_title')" />
<x-admin.textarea label="توضیح پشتیبانی تماس" name="home_sections[contact_support_body]" :value="data_get($publicContent, 'contact_support_body')" wide />
<x-admin.input label="تیتر سوالات متداول تماس" name="home_sections[contact_faq_title]" :value="data_get($publicContent, 'contact_faq_title')" />
@foreach (range(0, 7) as $contactFaqIndex)
  <x-admin.input label="پرسش تماس {{ $contactFaqIndex + 1 }}" name="home_sections[contact_faq_items][{{ $contactFaqIndex }}][question]" :value="$contactFaq[$contactFaqIndex]['question'] ?? ''" wide />
  <x-admin.textarea label="پاسخ پرسش تماس {{ $contactFaqIndex + 1 }}" name="home_sections[contact_faq_items][{{ $contactFaqIndex }}][answer]" :value="$contactFaq[$contactFaqIndex]['answer'] ?? ''" wide />
@endforeach
@foreach (range(0, 7) as $topicIndex)
  <x-admin.input label="موضوع فرم تماس {{ $topicIndex + 1 }}" name="home_sections[contact_topics][{{ $topicIndex }}]" :value="$publicContent['contact_topics'][$topicIndex] ?? ''" />
@endforeach
<x-admin.input label="تیتر صفحه سبد خرید" name="home_sections[cart_page_title]" :value="data_get($publicContent, 'cart_page_title')" />
<x-admin.textarea label="توضیح صفحه سبد خرید" name="home_sections[cart_page_description]" :value="data_get($publicContent, 'cart_page_description')" wide />

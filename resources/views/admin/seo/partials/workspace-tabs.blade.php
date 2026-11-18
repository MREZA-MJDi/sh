<nav class="adm-actions" aria-label="بخش‌های مدیریت محتوای صفحه اصلی و سئو" style="margin:0 0 18px;flex-wrap:wrap">
  <x-admin.btn :variant="request()->routeIs('admin.seo.banners.*') ? 'primary' : 'line'" icon="image" :href="route('admin.seo.banners.index', ['placement' => 'home_hero'])">Hero Slider</x-admin.btn>
  <x-admin.btn :variant="request()->routeIs('admin.seo.articles.*') ? 'primary' : 'line'" icon="file" :href="route('admin.seo.articles.index')">Blog</x-admin.btn>
  <x-admin.btn :variant="request()->routeIs('admin.seo.quiz.*') ? 'primary' : 'line'" icon="help" :href="route('admin.seo.quiz.index')">Quiz & Riddles</x-admin.btn>
</nav>

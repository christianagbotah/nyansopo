<?php
/**
 * Enterprise-Grade UI Components Library
 * Reusable components for Finance & Accounts modules
 */

// Page Header
function render_page_header($title, $subtitle = '', $back_url = '') {
    ?>
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo $title; ?></h1>
                <?php if($subtitle): ?>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?php echo $subtitle; ?></p>
                <?php endif; ?>
            </div>
            <?php if($back_url): ?>
            <a href="<?php echo $back_url; ?>" class="inline-flex items-center px-5 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Stat Card
function render_stat_card($title, $value, $icon_svg, $color = 'blue') {
    $colors = [
        'blue' => 'border-blue-500 bg-blue-100 text-blue-600',
        'green' => 'border-green-500 bg-green-100 text-green-600',
        'red' => 'border-red-500 bg-red-100 text-red-600',
        'yellow' => 'border-yellow-500 bg-yellow-100 text-yellow-600',
    ];
    $c = explode(' ', $colors[$color] ?? $colors['blue']);
    ?>
    <div class="bg-white rounded-xl shadow-sm border-l-4 <?php echo $c[0]; ?> p-6 hover:shadow-lg transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 mb-2"><?php echo $title; ?></p>
                <h3 class="text-3xl font-bold text-gray-900"><?php echo $value; ?></h3>
            </div>
            <div class="<?php echo $c[1]; ?> rounded-full p-4">
                <?php echo $icon_svg; ?>
            </div>
        </div>
    </div>
    <?php
}

// Status Badge
function render_badge($text, $type = 'info') {
    $types = [
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-blue-100 text-blue-800',
    ];
    ?>
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $types[$type] ?? $types['info']; ?>">
        <?php echo $text; ?>
    </span>
    <?php
}
?>

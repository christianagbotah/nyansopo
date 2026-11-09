<script src="<?php echo base_url(); ?>assets/cdn/js/tailwindcss.js"></script>
<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo get_phrase('noticeboard');?></h2>
        <p class="text-gray-600"><?php echo get_phrase('view_school_announcements_and_notices');?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        $count = 1;
        foreach ($notices as $row):
            $priority_colors = ['bg-blue-100 text-blue-800', 'bg-yellow-100 text-yellow-800', 'bg-red-100 text-red-800'];
            $priority_color = $priority_colors[array_rand($priority_colors)];
        ?>
        <div class="bg-white border border-gray-200 rounded-lg hover:shadow-lg transition overflow-hidden">
            <div class="p-6">
                <div class="flex items-start justify-between mb-3">
                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full <?php echo $priority_color;?>">
                        <?php echo get_phrase('notice');?>
                    </span>
                    <span class="text-sm text-gray-500"><?php echo date('d M, Y', $row['create_timestamp']); ?></span>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3 line-clamp-2"><?php echo $row['notice_title']; ?></h3>
                <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo strip_tags($row['notice']); ?></p>
                <button onclick="showAjaxModal('<?php echo site_url('modal/popup/modal_view_notice/'.$row['notice_id']); ?>');" class="w-full bg-blue-50 hover:bg-blue-100 text-blue-600 px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                    <i class="entypo-eye"></i> <?php echo get_phrase('view_details'); ?>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if(empty($notices)): ?>
    <div class="text-center py-20">
        <div class="bg-gray-100 rounded-full p-8 inline-block mb-4">
            <i class="entypo-info text-6xl text-gray-400"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo get_phrase('no_notices_available'); ?></h3>
        <p class="text-gray-500"><?php echo get_phrase('check_back_later_for_updates'); ?></p>
    </div>
    <?php endif; ?>

    <div class="hidden">
        <table class="datatable" id="table_export">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo get_phrase('title'); ?></th>
                    <th><?php echo get_phrase('notice'); ?></th>
                    <th><?php echo get_phrase('date'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 1;
                foreach ($notices as $row):
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo $row['notice_title']; ?></td>
                    <td><?php echo $row['notice']; ?></td>
                    <td><?php echo date('d M,Y', $row['create_timestamp']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
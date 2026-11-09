<div class="p-6 border-b">
    <?php echo form_open('', array('class' => 'relative', 'role' => 'form', 'method' => 'get'));?>
        <input type="text" name="s" placeholder="Search messages..." class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
        <i class="entypo-search absolute left-3 top-3 text-gray-400"></i>
    </form>
</div>

<div class="flex flex-col items-center justify-center p-20 text-center">
    <div class="bg-gray-100 rounded-full p-8 mb-6">
        <i class="entypo-mail text-6xl text-gray-400"></i>
    </div>
    <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo get_phrase('no_message_selected'); ?></h3>
    <p class="text-gray-500"><?php echo get_phrase('select_a_conversation_to_start_messaging'); ?></p>
</div>
<?php
// Path to the new visitor flag file
$sound_flag_file = 'newvisitor.flag';

// Check if the flag file exists
if (file_exists($sound_flag_file)) {
    // Remove the flag after detecting it, so sound plays only once per new visitor
    unlink($sound_flag_file);
    echo json_encode(['newVisitor' => true]);
} else {
    echo json_encode(['newVisitor' => false]);
}

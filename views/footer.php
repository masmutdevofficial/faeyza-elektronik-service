</main><?php if($user): ?></div></div><?php endif; ?><?php if($user && $user['role']==='admin' && !empty($GLOBALS['quick_status_dialog'])) require ROOT.'/views/status-dialog.php'; ?></body></html>

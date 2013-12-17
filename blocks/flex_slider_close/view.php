<?php  defined('C5_EXECUTE') or die("Access Denied.");
global $c;
?>


<?php if(!$c->isEditMode()): ?>

      </ul>
    </div>
</div>

<?php endif; ?>

<?php if($c->isEditMode()): ?>
This block has no information in edit mode 
<?php endif; ?>
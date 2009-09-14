<div id="languageDialog" title="<?php echo Yii::t('core', 'chooseLanguage'); ?>">
	<table>
		<tr>
		<?php if (count($languages) > 0 ) {?>
			<?php $i = 0; ?>
			<?php $languageCount = count($languages); ?>
			<?php foreach($languages AS $language) { ?>

				<td style="width: 150px;">
					<a href="<?php echo $language['url']; ?>" class="icon">
						<img src="<?php echo BASEURL . '/' . $language['icon']; ?>" alt="test" />
						<span><?php echo $language['label']; ?></span>
					</a>
				</td>

				<?php $i++; ?>
				<?php if ($i % 3 == 0 && $languageCount > $i) { ?>
					</tr><tr>
				<?php } ?>


			<?php } ?>
		<?php } else { ?>
			<td>
				<?php echo Yii::t('core', 'noOtherLanguagesAvailable'); ?>
			</td>
		<?php } ?>
		</tr>
	</table>
	<a href="http://www.chive-project.com" style="float:right; margin-top: 20px;">Help translating this project...</a>
</div>

<div id="themeDialog" title="<?php echo Yii::t('core', 'chooseTheme'); ?>">
	<table>
		<tr>
		<?php if (count($themes) > 0 ) {?>
			<?php $i = 0; ?>
			<?php $themeCount = count($themes); ?>
			<?php foreach($themes AS $theme) { ?>

				<td style="width: 150px;">
					<a href="<?php echo $theme['url']; ?>" class="icon">
						<img src="<?php echo BASEURL . '/' . $theme['icon']; ?>" alt="test" />
						<span><?php echo $theme['label']; ?></span>
					</a>
				</td>

				<?php $i++; ?>
				<?php if ($i % 3 == 0 && $themeCount > $i) { ?>
					</tr><tr>
				<?php } ?>

			<?php } ?>
		<?php } else { ?>
			<td>
				<?php echo Yii::t('core', 'noOtherThemesAvailable'); ?>
			</td>
	<?php } ?>
		</tr>
	</table>
</div>

<div id="login">
	<div id="login-logo">
		<img src="../images/logo-big.png"  />
	</div>
	
	<?php if($validBrowser) { ?>
	
		<?php echo CHtml::errorSummary($form, '', ''); ?>
	
		<div id="login-form">
			<?php echo CHtml::form(); ?>
			<div class="formItems non-floated" style="text-align: left;">
				<div class="item row1">
					<div class="left">
						<span class="icon">
							<?php #echo Html::icon('server'); ?>
							<?php echo CHtml::activeLabel($form,'host'); ?>
						</span>
					</div>
					<div class="right">
						<?php echo CHtml::activeTextField($form, 'host', array('value'=>'localhost', 'class'=>'text')); ?>
					</div>
				</div>
				<div class="item row2">
					<div class="left" style="float: none;">
						<span class="icon">	
							<?php #echo Html::icon('user'); ?>
							<?php echo CHtml::activeLabel($form,'username'); ?>
						</span>
					</div>
					<div class="right">
						<?php echo CHtml::activeTextField($form,'username', array('class'=>'text')) ?>
					</div>
				</div>
				<div class="item row1">
					<div class="left">
						<span class="icon">
							<?php #echo Html::icon('key_primary'); ?>
							<?php echo CHtml::activeLabel($form,'password'); ?>
						</span>
					</div>
					<div class="right">
						<?php echo CHtml::activePasswordField($form,'password', array('class'=>'text')); ?>
					</div>
				</div>
			</div>
	
			<div class="buttons">
				<a class="icon button primary" href="javascript:void(0);" onclick="$('form').submit()">
					<?php echo Html::icon('login', 16, false, 'core.login'); ?>
					<span><?php echo Yii::t('core', 'login'); ?></span>
				</a>
				<input type="submit" value="<?php echo Yii::t('core', 'login'); ?>" style="display: none" />
			</div>
	
			<?php echo CHtml::closeTag('form'); ?>
		</div>
	<?php } else { ?>
		<div id="loginform">
			<?php echo Yii::t('core', 'incompatibleBrowserWarning'); ?>
		</div>
	<?php } ?>

</div>
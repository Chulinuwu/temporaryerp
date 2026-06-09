<?php
/** @var array $profile */
$profile = $profile ?? [];
?>
<h1 class="kt-page-title">スタッフ設定</h1>

<div class="kt-card">
    <form action="/kintai/staff-settings" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= function_exists('csrf_token') ? csrf_token() : '' ?>">
        <div class="kt-card-body">
            <dl class="kt-dl">
                <dt>名前</dt>
                <dd><?= htmlspecialchars($profile['name'] ?? '') ?></dd>

                <dt>所属グループ</dt>
                <dd><?= htmlspecialchars($profile['group'] ?? '') ?></dd>

                <dt>所属サブグループ</dt>
                <dd><?= htmlspecialchars($profile['sub_group'] ?? '') ?></dd>

                <dt>スタッフ種別</dt>
                <dd><?= htmlspecialchars($profile['staff_type'] ?? '') ?></dd>

                <dt>電話番号</dt>
                <dd>
                    <input class="kt-input" type="tel" name="phone" style="width:520px;"
                           value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                </dd>

                <dt>メールアドレス</dt>
                <dd>
                    <?= htmlspecialchars($profile['email'] ?? '') ?>
                    <a href="#" style="margin-left:10px;">設定</a>
                </dd>

                <dt>パスワード</dt>
                <dd>
                    <span style="letter-spacing:2px;">********</span>
                    <a href="#" style="margin-left:10px;">設定</a>
                </dd>
            </dl>
        </div>
        <div class="kt-card-footer">
            <button type="submit" class="kt-btn kt-btn-primary">確認画面へ進む</button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/recipes_lib.php';
$user = require_login();
$id = get_int('id');
$r = $id ? row('SELECT * FROM recipes WHERE id = ?', array($id)) : null;
if ($id && !$r) { flash('Dieses Rezept gibt es nicht.', 'err'); redirect('rezepte.php'); }
if ($r && !is_owner_or_admin($user, $r['user_id'])) { flash('Du darfst nur deine eigenen Rezepte bearbeiten.', 'err'); redirect('rezept.php?id=' . $id); }

$errors = array();
$ings = $r ? rows('SELECT * FROM recipe_ingredients WHERE recipe_id = ? ORDER BY pos, id', array($id)) : array();

if (is_post()) {
    csrf_check();
    $title = post_str('title');
    $serv = max(1, min(200, post_int('servings', 4)));
    $prep = post_int('prep_min') ?: null; $cook = post_int('cook_min') ?: null;
    $cat = post_str('category'); if (!in_array($cat, recipe_categories(), true)) $cat = '';
    $amts = isset($_POST['ing_amount']) && is_array($_POST['ing_amount']) ? $_POST['ing_amount'] : array();
    $units = isset($_POST['ing_unit']) && is_array($_POST['ing_unit']) ? $_POST['ing_unit'] : array();
    $names = isset($_POST['ing_name']) && is_array($_POST['ing_name']) ? $_POST['ing_name'] : array();
    $new = array();
    foreach ($names as $k => $n) {
        $n = trim((string)$n);
        if ($n === '') continue;
        $a = parse_amount(isset($amts[$k]) ? $amts[$k] : '');
        if ($a === false) { $errors[] = 'Die Menge „' . trim((string)$amts[$k]) . '“ bei „' . $n . '“ verstehe ich nicht. Erlaubt sind Zahlen wie 250, 1,5 oder 1/2.'; $a = null; }
        $new[] = array('amount' => $a, 'unit' => mb_substr(trim(isset($units[$k]) ? (string)$units[$k] : ''), 0, 20), 'name' => mb_substr($n, 0, 120));
    }
    if ($title === '' || mb_strlen($title) > 120) $errors[] = 'Bitte gib dem Rezept einen Titel.';
    if (count($new) > 60) $errors[] = 'Es sind höchstens 60 Zutaten möglich.';
    $ings = array();
    foreach ($new as $n) $ings[] = array('amount' => $n['amount'], 'unit' => $n['unit'], 'name' => $n['name']);

    $image = $r ? $r['image'] : null; $oldImage = null;
    if (!$errors && !empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try { $res = image_save_upload($_FILES['image'], 'recipes', 1800, 480); $oldImage = $image; $image = $res['file']; }
        catch (Exception $e) { $errors[] = $e->getMessage(); }
    } elseif (!$errors && isset($_POST['remove_image']) && $image) { $oldImage = $image; $image = null; }

    if (!$errors) {
        $vals = array($title, mb_substr(post_str('description'), 0, 1500), $cat, $serv, $prep, $cook, mb_substr(post_str('steps'), 0, 10000), mb_substr(post_str('note'), 0, 3000), mb_substr(post_str('source'), 0, 300), $image);
        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($r) {
                q("UPDATE recipes SET title=?, description=?, category=?, servings=?, prep_min=?, cook_min=?, steps=?, note=?, source=?, image=?, updated_at=datetime('now') WHERE id=?", array_merge($vals, array($id)));
                q('DELETE FROM recipe_ingredients WHERE recipe_id = ?', array($id));
            } else {
                q('INSERT INTO recipes (title, description, category, servings, prep_min, cook_min, steps, note, source, image, user_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, array($user['id'])));
                $id = last_id();
            }
            foreach ($new as $pos => $n) q('INSERT INTO recipe_ingredients (recipe_id, pos, amount, unit, name) VALUES (?,?,?,?,?)', array($id, $pos, $n['amount'], $n['unit'], $n['name']));
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); throw $e; }
        if ($oldImage) image_delete('recipes', $oldImage);
        flash('Das Rezept wurde gespeichert.');
        redirect('rezept.php?id=' . $id);
    }
    $r = array_merge($r ? $r : array('image' => null, 'id' => 0), array('title' => $title, 'description' => post_str('description'), 'category' => $cat, 'servings' => $serv,
        'prep_min' => $prep, 'cook_min' => $cook, 'steps' => post_str('steps'), 'note' => post_str('note'), 'source' => post_str('source')));
}
$v = function ($k, $d = '') use ($r) { return $r && isset($r[$k]) && $r[$k] !== null ? $r[$k] : $d; };
if (!$ings) $ings = array(array('amount' => null, 'unit' => '', 'name' => ''), array('amount' => null, 'unit' => '', 'name' => ''), array('amount' => null, 'unit' => '', 'name' => ''));

fam_start($id ? 'Rezept bearbeiten' : 'Neues Rezept', 'rezepte', $user, array('narrow' => true));
fam_heading('Rezepte', $id ? 'Rezept <em>bearbeiten</em>' : 'Neues <em>Rezept</em>');
foreach ($errors as $e) echo '<div class="alert alert-err">' . h($e) . '</div>';
?>
<form method="post" enctype="multipart/form-data" class="panel"><?= csrf_field() ?>
  <div class="form-group"><label>Titel</label><input name="title" required maxlength="120" value="<?= h($v('title')) ?>" placeholder="z. B. Omas Apfelkuchen"></div>
  <div class="form-2">
    <div class="form-group"><label>Kategorie</label><select name="category"><option value="">–</option><?php foreach (recipe_categories() as $c): ?><option<?= $v('category') === $c ? ' selected' : '' ?>><?= h($c) ?></option><?php endforeach ?></select></div>
    <div class="form-group"><label>Portionen (Grundmenge der Zutaten)</label><input type="number" name="servings" min="1" max="200" value="<?= (int)$v('servings', 4) ?>"></div>
  </div>
  <div class="form-2">
    <div class="form-group"><label>Vorbereitung (Minuten)</label><input type="number" name="prep_min" min="0" value="<?= h($v('prep_min')) ?>"></div>
    <div class="form-group"><label>Garen / Backen (Minuten)</label><input type="number" name="cook_min" min="0" value="<?= h($v('cook_min')) ?>"></div>
  </div>
  <div class="form-group"><label>Kurzbeschreibung</label><textarea name="description" rows="2" maxlength="1500"><?= h($v('description')) ?></textarea></div>

  <div class="form-group"><label>Zutaten (für die oben genannten Portionen)</label>
    <div id="ingRows">
      <?php foreach ($ings as $i): ?>
      <div class="ing-edit"><input name="ing_amount[]" placeholder="Menge" inputmode="decimal" value="<?= h(amount_text($i['amount'])) ?>"><input name="ing_unit[]" placeholder="Einheit" list="units" value="<?= h($i['unit']) ?>"><input name="ing_name[]" placeholder="Zutat" value="<?= h($i['name']) ?>"><button type="button" class="rm" aria-label="Zeile entfernen">×</button></div>
      <?php endforeach ?>
    </div>
    <datalist id="units"><option>g</option><option>kg</option><option>ml</option><option>l</option><option>EL</option><option>TL</option><option>Stück</option><option>Prise</option><option>Bund</option><option>Pck.</option><option>Dose</option></datalist>
    <button type="button" class="link-btn" id="addIng">+ Zutat hinzufügen</button>
    <div class="hint">Mengen dürfen Brüche sein, zum Beispiel 1/2 oder 1 1/2. Ohne Menge (etwa „Salz“) die Menge leer lassen.</div>
  </div>

  <div class="form-group"><label>Zubereitung (ein Schritt pro Zeile)</label><textarea name="steps" rows="9"><?= h($v('steps')) ?></textarea></div>
  <div class="form-group"><label>Tipp oder Anmerkung (optional)</label><textarea name="note" rows="3"><?= h($v('note')) ?></textarea></div>
  <div class="form-group"><label>Quelle (optional)</label><input name="source" maxlength="300" value="<?= h($v('source')) ?>" placeholder="Link oder „von Tante Maria“"></div>
  <div class="form-group"><label>Bild (optional)</label>
    <?php if ($v('image')): ?><div style="margin-bottom:.6rem"><img src="<?= h(img_url('recipes', $v('image'), true)) ?>" alt="" style="max-width:200px;display:block;margin-bottom:.4rem"><label class="check"><input type="checkbox" name="remove_image" value="1"> Bild entfernen</label></div><?php endif ?>
    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp"></div>
  <div class="row"><button class="btn btn-lime">Speichern</button><a class="btn btn-outline" href="<?= $id ? 'rezept.php?id=' . $id : 'rezepte.php' ?>">Abbrechen</a></div>
</form>
<?php
fam_end('<script>
(function(){
  var box=document.getElementById("ingRows");
  function row(){var d=document.createElement("div");d.className="ing-edit";
    d.innerHTML=\'<input name="ing_amount[]" placeholder="Menge" inputmode="decimal"><input name="ing_unit[]" placeholder="Einheit" list="units"><input name="ing_name[]" placeholder="Zutat"><button type="button" class="rm" aria-label="Zeile entfernen">×</button>\';return d;}
  document.getElementById("addIng").onclick=function(){var r=row();box.appendChild(r);r.children[2].focus();};
  box.addEventListener("click",function(e){var b=e.target.closest(".rm");if(b&&box.children.length>1)b.parentNode.remove();});
  box.addEventListener("keydown",function(e){if(e.key==="Enter"&&e.target.name==="ing_name[]"){e.preventDefault();var r=row();e.target.parentNode.after(r);r.children[0].focus();}});
})();
</script>');

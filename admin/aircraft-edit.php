<?php
// ============================================================
//  SL JetSpot — Admin Edit Aircraft
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image.php';

Auth::require();

$id = (int)($_GET['id'] ?? 0);
$ac = DB::query('SELECT * FROM aircraft WHERE id = ?', [$id])->fetch();
if (!$ac) { flash('error', 'Aircraft not found.'); redirect('aircraft.php'); }

$errors = [];
$data   = $ac;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Security token invalid.'; goto render; }

    $fields = ['airline', 'aircraft_type', 'variant', 'registration', 'country', 'country_flag',
               'date_captured', 'location', 'camera', 'lens', 'resolution', 'category', 'notes', 'sort_order'];
    foreach ($fields as $k) { $data[$k] = trim($_POST[$k] ?? ''); }
    $data['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    // If category is 'other' and a custom label was typed, use that as category
    if ($data['category'] === 'other' && !empty(trim($_POST['category_other'] ?? ''))) {
        $data['category'] = strtolower(trim($_POST['category_other']));
    }

    if (!$data['airline'])       $errors[] = 'Airline is required.';
    if (!$data['aircraft_type']) $errors[] = 'Aircraft type is required.';

    // Keep existing paths unless a new image is uploaded
    $imagePath = $ac['image_path'];
    $thumbPath = $ac['thumb_path'] ?? '';

    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($mime, ALLOWED_TYPES)) {
            $errors[] = 'Image must be JPEG, PNG, or WebP.';
        } elseif ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
            $errors[] = 'Image must be under ' . MAX_UPLOAD_MB . 'MB.';
        } else {
            try {
                ImageHelper::delete($ac['image_path'], $ac['thumb_path'] ?? '');
                $paths     = ImageHelper::process($file['tmp_name'], $file['name']);
                $imagePath = $paths['full'];
                $thumbPath = $paths['thumb'];
            } catch (RuntimeException $ex) {
                $errors[] = 'Image processing failed: ' . $ex->getMessage();
            }
        }
    }

    if (!$errors) {
        DB::query(
            'UPDATE aircraft SET
                airline=?, aircraft_type=?, variant=?, registration=?, country=?, country_flag=?,
                date_captured=?, location=?, camera=?, lens=?, resolution=?,
                image_path=?, thumb_path=?,
                category=?, notes=?, is_published=?, sort_order=?
             WHERE id=?',
            [
                $data['airline'], $data['aircraft_type'], $data['variant'],
                $data['registration'], $data['country'], $data['country_flag'],
                $data['date_captured'], $data['location'], $data['camera'],
                $data['lens'], $data['resolution'],
                $imagePath, $thumbPath,
                $data['category'], $data['notes'],
                $data['is_published'], (int)$data['sort_order'],
                $id,
            ]
        );
        flash('success', 'Aircraft updated!');
        redirect('aircraft.php');
    }
}

// All countries with flag emoji — sorted alphabetically
$countries = [
    ['flag' => '🇦🇫', 'name' => 'Afghanistan'],
    ['flag' => '🇦🇱', 'name' => 'Albania'],
    ['flag' => '🇩🇿', 'name' => 'Algeria'],
    ['flag' => '🇦🇩', 'name' => 'Andorra'],
    ['flag' => '🇦🇴', 'name' => 'Angola'],
    ['flag' => '🇦🇬', 'name' => 'Antigua & Barbuda'],
    ['flag' => '🇦🇷', 'name' => 'Argentina'],
    ['flag' => '🇦🇲', 'name' => 'Armenia'],
    ['flag' => '🇦🇺', 'name' => 'Australia'],
    ['flag' => '🇦🇹', 'name' => 'Austria'],
    ['flag' => '🇦🇿', 'name' => 'Azerbaijan'],
    ['flag' => '🇧🇸', 'name' => 'Bahamas'],
    ['flag' => '🇧🇭', 'name' => 'Bahrain'],
    ['flag' => '🇧🇩', 'name' => 'Bangladesh'],
    ['flag' => '🇧🇧', 'name' => 'Barbados'],
    ['flag' => '🇧🇾', 'name' => 'Belarus'],
    ['flag' => '🇧🇪', 'name' => 'Belgium'],
    ['flag' => '🇧🇿', 'name' => 'Belize'],
    ['flag' => '🇧🇯', 'name' => 'Benin'],
    ['flag' => '🇧🇹', 'name' => 'Bhutan'],
    ['flag' => '🇧🇴', 'name' => 'Bolivia'],
    ['flag' => '🇧🇦', 'name' => 'Bosnia & Herzegovina'],
    ['flag' => '🇧🇼', 'name' => 'Botswana'],
    ['flag' => '🇧🇷', 'name' => 'Brazil'],
    ['flag' => '🇧🇳', 'name' => 'Brunei'],
    ['flag' => '🇧🇬', 'name' => 'Bulgaria'],
    ['flag' => '🇧🇫', 'name' => 'Burkina Faso'],
    ['flag' => '🇧🇮', 'name' => 'Burundi'],
    ['flag' => '🇨🇻', 'name' => 'Cabo Verde'],
    ['flag' => '🇰🇭', 'name' => 'Cambodia'],
    ['flag' => '🇨🇲', 'name' => 'Cameroon'],
    ['flag' => '🇨🇦', 'name' => 'Canada'],
    ['flag' => '🇨🇫', 'name' => 'Central African Republic'],
    ['flag' => '🇹🇩', 'name' => 'Chad'],
    ['flag' => '🇨🇱', 'name' => 'Chile'],
    ['flag' => '🇨🇳', 'name' => 'China'],
    ['flag' => '🇨🇴', 'name' => 'Colombia'],
    ['flag' => '🇰🇲', 'name' => 'Comoros'],
    ['flag' => '🇨🇬', 'name' => 'Congo'],
    ['flag' => '🇨🇷', 'name' => 'Costa Rica'],
    ['flag' => '🇭🇷', 'name' => 'Croatia'],
    ['flag' => '🇨🇺', 'name' => 'Cuba'],
    ['flag' => '🇨🇾', 'name' => 'Cyprus'],
    ['flag' => '🇨🇿', 'name' => 'Czech Republic'],
    ['flag' => '🇩🇰', 'name' => 'Denmark'],
    ['flag' => '🇩🇯', 'name' => 'Djibouti'],
    ['flag' => '🇩🇲', 'name' => 'Dominica'],
    ['flag' => '🇩🇴', 'name' => 'Dominican Republic'],
    ['flag' => '🇪🇨', 'name' => 'Ecuador'],
    ['flag' => '🇪🇬', 'name' => 'Egypt'],
    ['flag' => '🇸🇻', 'name' => 'El Salvador'],
    ['flag' => '🇬🇶', 'name' => 'Equatorial Guinea'],
    ['flag' => '🇪🇷', 'name' => 'Eritrea'],
    ['flag' => '🇪🇪', 'name' => 'Estonia'],
    ['flag' => '🇸🇿', 'name' => 'Eswatini'],
    ['flag' => '🇪🇹', 'name' => 'Ethiopia'],
    ['flag' => '🇫🇯', 'name' => 'Fiji'],
    ['flag' => '🇫🇮', 'name' => 'Finland'],
    ['flag' => '🇫🇷', 'name' => 'France'],
    ['flag' => '🇬🇦', 'name' => 'Gabon'],
    ['flag' => '🇬🇲', 'name' => 'Gambia'],
    ['flag' => '🇬🇪', 'name' => 'Georgia'],
    ['flag' => '🇩🇪', 'name' => 'Germany'],
    ['flag' => '🇬🇭', 'name' => 'Ghana'],
    ['flag' => '🇬🇷', 'name' => 'Greece'],
    ['flag' => '🇬🇩', 'name' => 'Grenada'],
    ['flag' => '🇬🇹', 'name' => 'Guatemala'],
    ['flag' => '🇬🇳', 'name' => 'Guinea'],
    ['flag' => '🇬🇼', 'name' => 'Guinea-Bissau'],
    ['flag' => '🇬🇾', 'name' => 'Guyana'],
    ['flag' => '🇭🇹', 'name' => 'Haiti'],
    ['flag' => '🇭🇳', 'name' => 'Honduras'],
    ['flag' => '🇭🇺', 'name' => 'Hungary'],
    ['flag' => '🇮🇸', 'name' => 'Iceland'],
    ['flag' => '🇮🇳', 'name' => 'India'],
    ['flag' => '🇮🇩', 'name' => 'Indonesia'],
    ['flag' => '🇮🇷', 'name' => 'Iran'],
    ['flag' => '🇮🇶', 'name' => 'Iraq'],
    ['flag' => '🇮🇪', 'name' => 'Ireland'],
    ['flag' => '🇮🇱', 'name' => 'Israel'],
    ['flag' => '🇮🇹', 'name' => 'Italy'],
    ['flag' => '🇯🇲', 'name' => 'Jamaica'],
    ['flag' => '🇯🇵', 'name' => 'Japan'],
    ['flag' => '🇯🇴', 'name' => 'Jordan'],
    ['flag' => '🇰🇿', 'name' => 'Kazakhstan'],
    ['flag' => '🇰🇪', 'name' => 'Kenya'],
    ['flag' => '🇰🇮', 'name' => 'Kiribati'],
    ['flag' => '🇰🇼', 'name' => 'Kuwait'],
    ['flag' => '🇰🇬', 'name' => 'Kyrgyzstan'],
    ['flag' => '🇱🇦', 'name' => 'Laos'],
    ['flag' => '🇱🇻', 'name' => 'Latvia'],
    ['flag' => '🇱🇧', 'name' => 'Lebanon'],
    ['flag' => '🇱🇸', 'name' => 'Lesotho'],
    ['flag' => '🇱🇷', 'name' => 'Liberia'],
    ['flag' => '🇱🇾', 'name' => 'Libya'],
    ['flag' => '🇱🇮', 'name' => 'Liechtenstein'],
    ['flag' => '🇱🇹', 'name' => 'Lithuania'],
    ['flag' => '🇱🇺', 'name' => 'Luxembourg'],
    ['flag' => '🇲🇬', 'name' => 'Madagascar'],
    ['flag' => '🇲🇼', 'name' => 'Malawi'],
    ['flag' => '🇲🇾', 'name' => 'Malaysia'],
    ['flag' => '🇲🇻', 'name' => 'Maldives'],
    ['flag' => '🇲🇱', 'name' => 'Mali'],
    ['flag' => '🇲🇹', 'name' => 'Malta'],
    ['flag' => '🇲🇭', 'name' => 'Marshall Islands'],
    ['flag' => '🇲🇷', 'name' => 'Mauritania'],
    ['flag' => '🇲🇺', 'name' => 'Mauritius'],
    ['flag' => '🇲🇽', 'name' => 'Mexico'],
    ['flag' => '🇫🇲', 'name' => 'Micronesia'],
    ['flag' => '🇲🇩', 'name' => 'Moldova'],
    ['flag' => '🇲🇨', 'name' => 'Monaco'],
    ['flag' => '🇲🇳', 'name' => 'Mongolia'],
    ['flag' => '🇲🇪', 'name' => 'Montenegro'],
    ['flag' => '🇲🇦', 'name' => 'Morocco'],
    ['flag' => '🇲🇿', 'name' => 'Mozambique'],
    ['flag' => '🇲🇲', 'name' => 'Myanmar'],
    ['flag' => '🇳🇦', 'name' => 'Namibia'],
    ['flag' => '🇳🇷', 'name' => 'Nauru'],
    ['flag' => '🇳🇵', 'name' => 'Nepal'],
    ['flag' => '🇳🇱', 'name' => 'Netherlands'],
    ['flag' => '🇳🇿', 'name' => 'New Zealand'],
    ['flag' => '🇳🇮', 'name' => 'Nicaragua'],
    ['flag' => '🇳🇪', 'name' => 'Niger'],
    ['flag' => '🇳🇬', 'name' => 'Nigeria'],
    ['flag' => '🇰🇵', 'name' => 'North Korea'],
    ['flag' => '🇲🇰', 'name' => 'North Macedonia'],
    ['flag' => '🇳🇴', 'name' => 'Norway'],
    ['flag' => '🇴🇲', 'name' => 'Oman'],
    ['flag' => '🇵🇰', 'name' => 'Pakistan'],
    ['flag' => '🇵🇼', 'name' => 'Palau'],
    ['flag' => '🇵🇦', 'name' => 'Panama'],
    ['flag' => '🇵🇬', 'name' => 'Papua New Guinea'],
    ['flag' => '🇵🇾', 'name' => 'Paraguay'],
    ['flag' => '🇵🇪', 'name' => 'Peru'],
    ['flag' => '🇵🇭', 'name' => 'Philippines'],
    ['flag' => '🇵🇱', 'name' => 'Poland'],
    ['flag' => '🇵🇹', 'name' => 'Portugal'],
    ['flag' => '🇶🇦', 'name' => 'Qatar'],
    ['flag' => '🇷🇴', 'name' => 'Romania'],
    ['flag' => '🇷🇺', 'name' => 'Russia'],
    ['flag' => '🇷🇼', 'name' => 'Rwanda'],
    ['flag' => '🇰🇳', 'name' => 'Saint Kitts & Nevis'],
    ['flag' => '🇱🇨', 'name' => 'Saint Lucia'],
    ['flag' => '🇻🇨', 'name' => 'Saint Vincent & the Grenadines'],
    ['flag' => '🇼🇸', 'name' => 'Samoa'],
    ['flag' => '🇸🇲', 'name' => 'San Marino'],
    ['flag' => '🇸🇹', 'name' => 'São Tomé & Príncipe'],
    ['flag' => '🇸🇦', 'name' => 'Saudi Arabia'],
    ['flag' => '🇸🇳', 'name' => 'Senegal'],
    ['flag' => '🇷🇸', 'name' => 'Serbia'],
    ['flag' => '🇸🇨', 'name' => 'Seychelles'],
    ['flag' => '🇸🇱', 'name' => 'Sierra Leone'],
    ['flag' => '🇸🇬', 'name' => 'Singapore'],
    ['flag' => '🇸🇰', 'name' => 'Slovakia'],
    ['flag' => '🇸🇮', 'name' => 'Slovenia'],
    ['flag' => '🇸🇧', 'name' => 'Solomon Islands'],
    ['flag' => '🇸🇴', 'name' => 'Somalia'],
    ['flag' => '🇿🇦', 'name' => 'South Africa'],
    ['flag' => '🇸🇸', 'name' => 'South Sudan'],
    ['flag' => '🇪🇸', 'name' => 'Spain'],
    ['flag' => '🇱🇰', 'name' => 'Sri Lanka'],
    ['flag' => '🇸🇩', 'name' => 'Sudan'],
    ['flag' => '🇸🇷', 'name' => 'Suriname'],
    ['flag' => '🇸🇪', 'name' => 'Sweden'],
    ['flag' => '🇨🇭', 'name' => 'Switzerland'],
    ['flag' => '🇸🇾', 'name' => 'Syria'],
    ['flag' => '🇹🇼', 'name' => 'Taiwan'],
    ['flag' => '🇹🇯', 'name' => 'Tajikistan'],
    ['flag' => '🇹🇿', 'name' => 'Tanzania'],
    ['flag' => '🇹🇭', 'name' => 'Thailand'],
    ['flag' => '🇹🇱', 'name' => 'Timor-Leste'],
    ['flag' => '🇹🇬', 'name' => 'Togo'],
    ['flag' => '🇹🇴', 'name' => 'Tonga'],
    ['flag' => '🇹🇹', 'name' => 'Trinidad & Tobago'],
    ['flag' => '🇹🇳', 'name' => 'Tunisia'],
    ['flag' => '🇹🇷', 'name' => 'Turkey'],
    ['flag' => '🇹🇲', 'name' => 'Turkmenistan'],
    ['flag' => '🇹🇻', 'name' => 'Tuvalu'],
    ['flag' => '🇺🇬', 'name' => 'Uganda'],
    ['flag' => '🇺🇦', 'name' => 'Ukraine'],
    ['flag' => '🇦🇪', 'name' => 'United Arab Emirates'],
    ['flag' => '🇬🇧', 'name' => 'United Kingdom'],
    ['flag' => '🇺🇸', 'name' => 'United States'],
    ['flag' => '🇺🇾', 'name' => 'Uruguay'],
    ['flag' => '🇺🇿', 'name' => 'Uzbekistan'],
    ['flag' => '🇻🇺', 'name' => 'Vanuatu'],
    ['flag' => '🇻🇦', 'name' => 'Vatican City'],
    ['flag' => '🇻🇪', 'name' => 'Venezuela'],
    ['flag' => '🇻🇳', 'name' => 'Vietnam'],
    ['flag' => '🇾🇪', 'name' => 'Yemen'],
    ['flag' => '🇿🇲', 'name' => 'Zambia'],
    ['flag' => '🇿🇼', 'name' => 'Zimbabwe'],
];

// Find the current flag from saved country name
$currentFlag = $data['country_flag'];
if (!$currentFlag && $data['country']) {
    foreach ($countries as $c) {
        if (strcasecmp($c['name'], $data['country']) === 0) {
            $currentFlag = $c['flag'];
            break;
        }
    }
}

// Determine if category is custom
$isCustomCat  = !in_array($data['category'], ['airbus', 'boeing', 'other']);
$selectCatVal = $isCustomCat ? 'other' : $data['category'];
$customCatVal = $isCustomCat ? $data['category'] : '';

render:
require 'partials/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-pen"></i> Edit Aircraft #<?= $id ?></h1>
    <a href="aircraft.php" class="btn-admin-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>

    <!-- Aircraft Information -->
    <div class="admin-card">
        <h2 class="card-section-title">Aircraft Information</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Airline <span class="req">*</span></label>
                <input type="text" name="airline" value="<?= e($data['airline']) ?>" required>
            </div>
            <div class="form-group">
                <label>Aircraft Type <span class="req">*</span></label>
                <input type="text" name="aircraft_type" value="<?= e($data['aircraft_type']) ?>" required>
            </div>
            <div class="form-group">
                <label>Variant</label>
                <input type="text" name="variant" value="<?= e($data['variant']) ?>">
            </div>
            <div class="form-group">
                <label>Registration</label>
                <input type="text" name="registration" value="<?= e($data['registration']) ?>">
            </div>

            <!-- Country Picker -->
            <div class="form-group country-picker-wrap">
                <label>Country &amp; Flag</label>
                <input type="hidden" name="country"      id="countryValue"     value="<?= e($data['country']) ?>">
                <input type="hidden" name="country_flag" id="countryFlagValue" value="<?= e($currentFlag) ?>">

                <div class="country-picker" id="countryPicker">
                    <div class="country-input-wrap">
                        <span class="country-flag-preview" id="flagPreview">
                            <?= $currentFlag ?: '🌍' ?>
                        </span>
                        <input type="text"
                               id="countrySearch"
                               class="country-search"
                               placeholder="Search country..."
                               value="<?= e($data['country']) ?>"
                               autocomplete="off">
                        <button type="button" class="country-clear" id="countryClear" title="Clear">×</button>
                    </div>
                    <div class="country-dropdown" id="countryDropdown">
                        <?php foreach ($countries as $c): ?>
                        <div class="country-option <?= $c['name'] === $data['country'] ? 'selected' : '' ?>"
                             data-name="<?= e($c['name']) ?>"
                             data-flag="<?= e($c['flag']) ?>">
                            <span class="co-flag"><?= $c['flag'] ?></span>
                            <span class="co-name"><?= e($c['name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Category with custom Other input -->
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="categorySelect">
                    <option value="airbus" <?= $selectCatVal === 'airbus' ? 'selected' : '' ?>>Airbus</option>
                    <option value="boeing" <?= $selectCatVal === 'boeing' ? 'selected' : '' ?>>Boeing</option>
                    <option value="other"  <?= $selectCatVal === 'other'  ? 'selected' : '' ?>>Other</option>
                </select>
                <input type="text"
                       name="category_other"
                       id="categoryOther"
                       value="<?= e($customCatVal) ?>"
                       placeholder="e.g. Military, Private, Cargo, Government..."
                       list="manufacturerList"
                       style="margin-top:8px;display:<?= $selectCatVal === 'other' ? 'block' : 'none' ?>">
                <datalist id="manufacturerList">
                    <option value="Lockheed Martin">
                    <option value="Bombardier">
                    <option value="Embraer">
                    <option value="ATR">
                    <option value="Cessna">
                    <option value="Beechcraft">
                    <option value="Piper">
                    <option value="Dassault">
                    <option value="Gulfstream">
                    <option value="Saab">
                    <option value="Fokker">
                    <option value="De Havilland">
                    <option value="Antonov">
                    <option value="Tupolev">
                    <option value="Ilyushin">
                    <option value="Sukhoi">
                    <option value="Comac">
                    <option value="Mitsubishi">
                    <option value="Military">
                    <option value="Private">
                    <option value="Cargo">
                    <option value="Government">
                    <option value="Charter">
                    <option value="Helicopter">
                </datalist>
                <small id="categoryOtherHint" style="display:<?= $selectCatVal === 'other' ? 'block' : 'none' ?>;color:var(--muted);font-size:0.78rem;margin-top:4px">
                    This label will be saved as the category. Keep it short (e.g. "military").
                </small>
            </div>
        </div>
    </div>

    <!-- Capture Details -->
    <div class="admin-card">
        <h2 class="card-section-title">Capture Details</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Date Captured</label>
                <input type="date" name="date_captured" value="<?= e($data['date_captured']) ?>">
            </div>
            <div class="form-group">
                <label>Airport (ICAO)</label>
                <input type="text" name="location" value="<?= e($data['location']) ?>">
            </div>
            <div class="form-group">
                <label>Camera</label>
                <input type="text" name="camera" value="<?= e($data['camera']) ?>">
            </div>
            <div class="form-group">
                <label>Lens</label>
                <input type="text" name="lens" value="<?= e($data['lens']) ?>">
            </div>
            <div class="form-group">
                <label>Resolution</label>
                <input type="text" name="resolution" value="<?= e($data['resolution']) ?>">
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= (int)$data['sort_order'] ?>">
            </div>
        </div>
        <div class="form-group" style="margin-top:16px">
            <label>Notes</label>
            <textarea name="notes" rows="3"><?= e($data['notes']) ?></textarea>
        </div>
    </div>

    <!-- Image -->
    <div class="admin-card">
        <h2 class="card-section-title">Image</h2>
        <div class="edit-img-row">
            <?php if ($data['thumb_path']): ?>
            <div>
                <p class="edit-img-label">Current thumbnail (gallery)</p>
                <img src="../<?= e($data['thumb_path']) ?>" alt="Thumbnail"
                     style="max-height:160px;border-radius:8px;border:1px solid var(--border)">
            </div>
            <?php endif; ?>
            <div>
                <p class="edit-img-label">Current full image (lightbox)</p>
                <img src="../<?= e($data['image_path']) ?>" alt="Full image"
                     style="max-height:160px;border-radius:8px;border:1px solid var(--border)">
            </div>
        </div>
        <div class="upload-notice" style="margin-top:16px">
            <i class="fas fa-magic"></i>
            <span>Uploading a new image will <strong>auto-compress</strong> it and replace both the thumbnail and full image. Leave empty to keep the current image.</span>
        </div>
        <div class="upload-zone" id="uploadZone" style="margin-top:12px">
            <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp">
            <div class="upload-placeholder" id="uploadPlaceholder">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Upload new image to replace (optional)</p>
                <span>JPEG · PNG · WebP · Max <?= MAX_UPLOAD_MB ?>MB · Auto-compressed</span>
            </div>
            <img id="uploadPreview" src="" alt="Preview" style="display:none;max-height:200px;border-radius:8px;">
        </div>
    </div>

    <!-- Publishing -->
    <div class="admin-card">
        <h2 class="card-section-title">Publishing</h2>
        <label class="toggle-label">
            <input type="checkbox" name="is_published" value="1" <?= $data['is_published'] ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
            Publish to gallery
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-admin-primary"><i class="fas fa-save"></i> Update Aircraft</button>
        <a href="aircraft.php" class="btn-admin-outline">Cancel</a>
    </div>
</form>

<style>
.edit-img-row { display: flex; gap: 24px; flex-wrap: wrap; margin-bottom: 4px; }
.edit-img-label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 6px; }
.upload-notice { background: var(--accent-light); border: 1px solid var(--accent); border-radius: 8px; padding: 12px 16px; font-size: .88rem; color: var(--accent-dark); display: flex; align-items: flex-start; gap: 10px; }
.upload-notice i { margin-top: 2px; flex-shrink: 0; }

/* ===== COUNTRY PICKER ===== */
.country-picker { position: relative; }
.country-input-wrap {
    display: flex; align-items: center;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    background: var(--white); overflow: hidden; transition: border-color 0.2s;
}
.country-input-wrap:focus-within { border-color: var(--accent); }
.country-flag-preview {
    padding: 0 12px; font-size: 1.3rem; line-height: 1; flex-shrink: 0;
    border-right: 1px solid var(--border); height: 42px;
    display: flex; align-items: center; background: var(--off-white); user-select: none;
}
.country-search {
    flex: 1; border: none !important; outline: none !important;
    padding: 10px 12px; font-size: 0.9rem; background: transparent;
    color: var(--text); font-family: inherit;
}
.country-clear {
    padding: 0 12px; font-size: 1.2rem; color: var(--muted); line-height: 1;
    flex-shrink: 0; background: none; border: none; cursor: pointer; transition: color 0.2s;
}
.country-clear:hover { color: var(--text); }
.country-dropdown {
    display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); max-height: 240px; overflow-y: auto;
    z-index: 50; box-shadow: var(--shadow-md);
}
.country-dropdown.open { display: block; }
.country-option {
    display: flex; align-items: center; gap: 12px;
    padding: 9px 14px; cursor: pointer; transition: background 0.15s; font-size: 0.88rem;
}
.country-option:hover,
.country-option.highlighted { background: var(--accent-light); }
.country-option.selected { background: var(--accent-light); font-weight: 600; }
.country-option.hidden { display: none; }
.co-flag { font-size: 1.2rem; line-height: 1; flex-shrink: 0; }
.co-name { color: var(--text); }
</style>

<script>
// ===== IMAGE PREVIEW =====
const input       = document.getElementById('imageInput');
const preview     = document.getElementById('uploadPreview');
const placeholder = document.getElementById('uploadPlaceholder');
input.addEventListener('change', () => {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
});

// ===== COUNTRY PICKER =====
(function() {
    const searchInput = document.getElementById('countrySearch');
    const dropdown    = document.getElementById('countryDropdown');
    const flagPreview = document.getElementById('flagPreview');
    const countryVal  = document.getElementById('countryValue');
    const flagVal     = document.getElementById('countryFlagValue');
    const clearBtn    = document.getElementById('countryClear');
    const options     = Array.from(dropdown.querySelectorAll('.country-option'));
    let highlighted   = -1;

    function openDropdown() { dropdown.classList.add('open'); highlighted = -1; }
    function closeDropdown() { dropdown.classList.remove('open'); }

    function selectCountry(name, flag) {
        searchInput.value       = name;
        flagPreview.textContent = flag;
        countryVal.value        = name;
        flagVal.value           = flag;
        options.forEach(o => o.classList.toggle('selected', o.dataset.name === name));
        closeDropdown();
        filterOptions('');
    }

    function filterOptions(query) {
        const q = query.toLowerCase().trim();
        options.forEach(opt => {
            opt.classList.toggle('hidden', !opt.dataset.name.toLowerCase().includes(q));
        });
        highlighted = -1;
    }

    function highlightOption(index) {
        options.forEach(o => o.classList.remove('highlighted'));
        const visible = options.filter(o => !o.classList.contains('hidden'));
        if (index < 0 || index >= visible.length) return;
        visible[index].classList.add('highlighted');
        visible[index].scrollIntoView({ block: 'nearest' });
        highlighted = index;
    }

    searchInput.addEventListener('focus', () => { filterOptions(searchInput.value); openDropdown(); });
    searchInput.addEventListener('input', () => { openDropdown(); filterOptions(searchInput.value); });

    searchInput.addEventListener('keydown', e => {
        const visible = options.filter(o => !o.classList.contains('hidden'));
        if (e.key === 'ArrowDown') { e.preventDefault(); highlightOption(Math.min(highlighted + 1, visible.length - 1)); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlightOption(Math.max(highlighted - 1, 0)); }
        else if (e.key === 'Enter') { e.preventDefault(); if (highlighted >= 0 && visible[highlighted]) selectCountry(visible[highlighted].dataset.name, visible[highlighted].dataset.flag); }
        else if (e.key === 'Escape') closeDropdown();
    });

    options.forEach(opt => {
        opt.addEventListener('mousedown', e => { e.preventDefault(); selectCountry(opt.dataset.name, opt.dataset.flag); });
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = ''; flagPreview.textContent = '🌍';
        countryVal.value = ''; flagVal.value = '';
        options.forEach(o => o.classList.remove('selected'));
        filterOptions(''); searchInput.focus();
    });

    document.addEventListener('click', e => { if (!e.target.closest('#countryPicker')) closeDropdown(); });

    // Scroll to selected option when dropdown opens
    searchInput.addEventListener('focus', () => {
        const sel = dropdown.querySelector('.selected');
        if (sel) setTimeout(() => sel.scrollIntoView({ block: 'nearest' }), 50);
    });
})();

// ===== CATEGORY OTHER TOGGLE =====
const categorySelect = document.getElementById('categorySelect');
const categoryOther  = document.getElementById('categoryOther');
const categoryHint   = document.getElementById('categoryOtherHint');

function toggleCategoryOther() {
    const isOther = categorySelect.value === 'other';
    categoryOther.style.display = isOther ? 'block' : 'none';
    categoryHint.style.display  = isOther ? 'block' : 'none';
    if (isOther) categoryOther.focus();
    else categoryOther.value = '';
}
categorySelect.addEventListener('change', toggleCategoryOther);
</script>

<?php require 'partials/footer.php'; ?>
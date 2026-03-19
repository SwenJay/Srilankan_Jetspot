<?php
// ============================================================
//  SL JetSpot — Admin Add Aircraft
// ============================================================
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image.php';

Auth::require();

$errors = [];
$data   = [
    'airline'       => '', 'aircraft_type' => '', 'variant'      => '',
    'registration'  => 'UNKNOWN', 'country'  => '', 'country_flag' => '',
    'date_captured' => date('Y-m-d'), 'location' => 'VCBI',
    'camera'        => 'Canon EOS 4000D', 'lens'  => '250mm',
    'resolution'    => '5184×3456px', 'category' => 'airbus',
    'notes'         => '', 'is_published' => 1, 'sort_order' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Security token invalid.'; goto render; }

    foreach (array_keys($data) as $k) {
        $data[$k] = trim($_POST[$k] ?? $data[$k]);
    }
    $data['is_published'] = isset($_POST['is_published']) ? 1 : 0;

    // If category is 'other' and a custom label was typed, use that as category
    if ($data['category'] === 'other' && !empty(trim($_POST['category_other'] ?? ''))) {
        $data['category'] = strtolower(trim($_POST['category_other']));
    }

    if (!$data['airline'])       $errors[] = 'Airline is required.';
    if (!$data['aircraft_type']) $errors[] = 'Aircraft type is required.';
    if (!$data['date_captured']) $errors[] = 'Date is required.';

    $imagePath = '';
    $thumbPath = '';

    if (!empty($_FILES['image']['name'])) {
        $file = $_FILES['image'];
        $mime = mime_content_type($file['tmp_name']);

        if (!in_array($mime, ALLOWED_TYPES)) {
            $errors[] = 'Image must be JPEG, PNG, or WebP.';
        } elseif ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
            $errors[] = 'Image must be under ' . MAX_UPLOAD_MB . 'MB.';
        } else {
            try {
                $paths     = ImageHelper::process($file['tmp_name'], $file['name']);
                $imagePath = $paths['full'];
                $thumbPath = $paths['thumb'];
            } catch (RuntimeException $ex) {
                $errors[] = 'Image processing failed: ' . $ex->getMessage();
            }
        }
    } else {
        $errors[] = 'Please upload an image.';
    }

    if (!$errors) {
        DB::query(
            'INSERT INTO aircraft
                (airline, aircraft_type, variant, registration, country, country_flag,
                 date_captured, location, camera, lens, resolution,
                 image_path, thumb_path,
                 category, notes, is_published, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['airline'], $data['aircraft_type'], $data['variant'],
                $data['registration'], $data['country'], $data['country_flag'],
                $data['date_captured'], $data['location'], $data['camera'],
                $data['lens'], $data['resolution'],
                $imagePath, $thumbPath,
                $data['category'], $data['notes'],
                $data['is_published'], (int)$data['sort_order'],
            ]
        );
        flash('success', 'Aircraft added — image auto-compressed!');
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

render:
require 'partials/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-plus"></i> Add Aircraft</h1>
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
                <input type="text" name="airline" value="<?= e($data['airline']) ?>" required placeholder="e.g. SriLankan Airlines">
            </div>
            <div class="form-group">
                <label>Aircraft Type <span class="req">*</span></label>
                <input type="text" name="aircraft_type" value="<?= e($data['aircraft_type']) ?>" required placeholder="e.g. Airbus A330-300">
            </div>
            <div class="form-group">
                <label>Variant</label>
                <input type="text" name="variant" value="<?= e($data['variant']) ?>" placeholder="e.g. A330-343">
            </div>
            <div class="form-group">
                <label>Registration</label>
                <input type="text" name="registration" value="<?= e($data['registration']) ?>" placeholder="e.g. 4R-ALM">
            </div>

            <!-- Country Picker -->
            <div class="form-group country-picker-wrap">
                <label>Country & Flag</label>
                <!-- Hidden fields submitted with form -->
                <input type="hidden" name="country"      id="countryValue"     value="<?= e($data['country']) ?>">
                <input type="hidden" name="country_flag" id="countryFlagValue" value="<?= e($data['country_flag']) ?>">

                <!-- Searchable display input -->
                <div class="country-picker" id="countryPicker">
                    <div class="country-input-wrap">
                        <span class="country-flag-preview" id="flagPreview">
                            <?= $data['country_flag'] ? e($data['country_flag']) : '🌍' ?>
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
                        <div class="country-option"
                             data-name="<?= e($c['name']) ?>"
                             data-flag="<?= e($c['flag']) ?>">
                            <span class="co-flag"><?= $c['flag'] ?></span>
                            <span class="co-name"><?= e($c['name']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Category <span class="req">*</span></label>
                <?php
                // If saved category is not airbus/boeing, it's a custom "other" value
                $isCustom    = !in_array($data['category'], ['airbus','boeing','other']);
                $selectVal   = $isCustom ? 'other' : $data['category'];
                $customLabel = $isCustom ? $data['category'] : '';
                ?>
                <select name="category" id="categorySelect">
                    <option value="airbus" <?= $selectVal === 'airbus' ? 'selected' : '' ?>>Airbus</option>
                    <option value="boeing" <?= $selectVal === 'boeing' ? 'selected' : '' ?>>Boeing</option>
                    <option value="other"  <?= $selectVal === 'other'  ? 'selected' : '' ?>>Other</option>
                </select>
                <input type="text"
                       name="category_other"
                       id="categoryOther"
                       value="<?= e($customLabel) ?>"
                       placeholder="e.g. Military, Private, Cargo, Government..."
                       list="manufacturerList"
                       style="margin-top:8px;display:<?= $selectVal === 'other' ? 'block' : 'none' ?>">
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
                <small id="categoryOtherHint" style="display:<?= $selectVal === 'other' ? 'block' : 'none' ?>;color:var(--muted);font-size:0.78rem;margin-top:4px">
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
                <label>Date Captured <span class="req">*</span></label>
                <input type="date" name="date_captured" value="<?= e($data['date_captured']) ?>" required>
            </div>
            <div class="form-group">
                <label>Airport (ICAO)</label>
                <input type="text" name="location" value="<?= e($data['location']) ?>" placeholder="eg : VCBI">
            </div>
            <div class="form-group">
                <label>Camera</label>
                <input type="text" name="camera" value="<?= e($data['camera']) ?>" placeholder="Canon EOS 4000D">
            </div>
            <div class="form-group">
                <label>Lens</label>
                <input type="text" name="lens" value="<?= e($data['lens']) ?>" placeholder="250mm">
            </div>
            <div class="form-group">
                <label>Resolution</label>
                <input type="text" name="resolution" value="<?= e($data['resolution']) ?>" placeholder="5184×3456px">
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= (int)$data['sort_order'] ?>" min="0">
            </div>
        </div>
        <div class="form-group" style="margin-top:16px">
            <label>Notes</label>
            <textarea name="notes" rows="3" placeholder="Optional notes about this shot..."><?= e($data['notes']) ?></textarea>
        </div>
    </div>

    <!-- Image Upload -->
    <div class="admin-card">
        <h2 class="card-section-title">Image Upload</h2>
        <div class="upload-notice">
            <i class="fas fa-magic"></i>
            <span>Images are <strong>auto-compressed</strong> on upload — a gallery thumbnail (800px) and full lightbox image (1920px) are generated automatically. Just upload your original photo.</span>
        </div>
        <div class="upload-zone" id="uploadZone">
            <input type="file" name="image" id="imageInput" accept="image/jpeg,image/png,image/webp" required>
            <div class="upload-placeholder" id="uploadPlaceholder">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click or drag &amp; drop image here</p>
                <span>JPEG · PNG · WebP · Max <?= MAX_UPLOAD_MB ?>MB · Auto-compressed on upload</span>
            </div>
            <img id="uploadPreview" src="" alt="Preview" style="display:none;max-height:250px;border-radius:8px;">
        </div>
    </div>

    <!-- Publishing -->
    <div class="admin-card">
        <h2 class="card-section-title">Publishing</h2>
        <label class="toggle-label">
            <input type="checkbox" name="is_published" value="1" <?= $data['is_published'] ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
            Publish to gallery immediately
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-admin-primary"><i class="fas fa-save"></i> Save &amp; Compress Image</button>
        <a href="aircraft.php" class="btn-admin-outline">Cancel</a>
    </div>
</form>

<style>
.upload-notice {
    background: var(--accent-light); border: 1px solid var(--accent);
    border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;
    font-size: .88rem; color: var(--accent-dark);
    display: flex; align-items: flex-start; gap: 10px;
}
.upload-notice i { margin-top: 2px; flex-shrink: 0; }

/* ===== COUNTRY PICKER ===== */
.country-picker { position: relative; }

.country-input-wrap {
    display: flex; align-items: center; gap: 0;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--white);
    overflow: hidden;
    transition: border-color 0.2s;
}
.country-input-wrap:focus-within { border-color: var(--accent); }

.country-flag-preview {
    padding: 0 12px;
    font-size: 1.3rem;
    line-height: 1;
    flex-shrink: 0;
    border-right: 1px solid var(--border);
    height: 42px; display: flex; align-items: center;
    background: var(--off-white);
    user-select: none;
}

.country-search {
    flex: 1;
    border: none !important;
    outline: none !important;
    padding: 10px 12px;
    font-size: 0.9rem;
    background: transparent;
    color: var(--text);
    font-family: inherit;
}

.country-clear {
    padding: 0 12px;
    font-size: 1.2rem;
    color: var(--muted);
    line-height: 1;
    flex-shrink: 0;
    background: none; border: none; cursor: pointer;
    transition: color 0.2s;
}
.country-clear:hover { color: var(--text); }

.country-dropdown {
    display: none;
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    max-height: 240px;
    overflow-y: auto;
    z-index: 50;
    box-shadow: var(--shadow-md);
}
.country-dropdown.open { display: block; }

.country-option {
    display: flex; align-items: center; gap: 12px;
    padding: 9px 14px;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 0.88rem;
}
.country-option:hover, .country-option.highlighted { background: var(--accent-light); }
.country-option.selected { background: var(--accent-light); font-weight: 600; }

.co-flag { font-size: 1.2rem; line-height: 1; flex-shrink: 0; }
.co-name { color: var(--text); }
.country-option.hidden { display: none; }
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

// ===== CATEGORY OTHER TOGGLE =====
const categorySelect = document.getElementById('categorySelect');
const categoryOther  = document.getElementById('categoryOther');
const categoryHint   = document.getElementById('categoryOtherHint');

function toggleCategoryOther() {
    const isOther = categorySelect.value === 'other';
    categoryOther.style.display = isOther ? 'block' : 'none';
    categoryHint.style.display  = isOther ? 'block' : 'none';
    if (isOther) {
        categoryOther.focus();
    } else {
        categoryOther.value = '';
    }
}
categorySelect.addEventListener('change', toggleCategoryOther);

// ===== COUNTRY PICKER =====
(function() {
    const searchInput  = document.getElementById('countrySearch');
    const dropdown     = document.getElementById('countryDropdown');
    const flagPreview  = document.getElementById('flagPreview');
    const countryVal   = document.getElementById('countryValue');
    const flagVal      = document.getElementById('countryFlagValue');
    const clearBtn     = document.getElementById('countryClear');
    const options      = Array.from(dropdown.querySelectorAll('.country-option'));

    let highlighted = -1;

    function openDropdown() {
        dropdown.classList.add('open');
        highlighted = -1;
    }

    function closeDropdown() {
        dropdown.classList.remove('open');
    }

    function selectCountry(name, flag) {
        searchInput.value  = name;
        flagPreview.textContent = flag;
        countryVal.value   = name;
        flagVal.value      = flag;
        closeDropdown();
        filterOptions(''); // reset filter so all show next time
    }

    function filterOptions(query) {
        const q = query.toLowerCase().trim();
        let visibleCount = 0;
        options.forEach((opt, i) => {
            const match = opt.dataset.name.toLowerCase().includes(q);
            opt.classList.toggle('hidden', !match);
            if (match) visibleCount++;
        });
        highlighted = -1;
        return visibleCount;
    }

    function highlightOption(index) {
        options.forEach(o => o.classList.remove('highlighted'));
        const visible = options.filter(o => !o.classList.contains('hidden'));
        if (index < 0 || index >= visible.length) return;
        visible[index].classList.add('highlighted');
        visible[index].scrollIntoView({ block: 'nearest' });
        highlighted = index;
    }

    // Open on focus
    searchInput.addEventListener('focus', () => {
        filterOptions(searchInput.value);
        openDropdown();
    });

    // Filter as you type
    searchInput.addEventListener('input', () => {
        openDropdown();
        filterOptions(searchInput.value);
    });

    // Keyboard nav
    searchInput.addEventListener('keydown', e => {
        const visible = options.filter(o => !o.classList.contains('hidden'));
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlightOption(Math.min(highlighted + 1, visible.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlightOption(Math.max(highlighted - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (highlighted >= 0 && visible[highlighted]) {
                selectCountry(visible[highlighted].dataset.name, visible[highlighted].dataset.flag);
            }
        } else if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    // Click option
    options.forEach(opt => {
        opt.addEventListener('mousedown', e => {
            e.preventDefault(); // prevent blur before click
            selectCountry(opt.dataset.name, opt.dataset.flag);
        });
    });

    // Clear button
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        flagPreview.textContent = '🌍';
        countryVal.value  = '';
        flagVal.value     = '';
        filterOptions('');
        searchInput.focus();
    });

    // Close on outside click
    document.addEventListener('click', e => {
        if (!e.target.closest('#countryPicker')) closeDropdown();
    });
})();
</script>

<?php require 'partials/footer.php'; ?>
// Backend UserProfile bilan mos qiymatlar (kalitlar inglizcha).

typedef ProfileOption = ({String value, String label});

const List<ProfileOption> kGenderOptions = [
  (value: 'male', label: 'Erkak'),
  (value: 'female', label: 'Ayol'),
];

const List<ProfileOption> kSkinToneOptions = [
  (value: 'light', label: 'Oq teri'),
  (value: 'warm_medium', label: 'Iliq bug‘doy'),
  (value: 'cool_medium', label: 'Sovuq bug‘doy'),
  (value: 'dark', label: 'To‘q teri'),
];

const List<ProfileOption> kFaceShapeOptions = [
  (value: 'oval', label: 'Oval'),
  (value: 'round', label: 'Dumaloq'),
  (value: 'square', label: 'To‘rtburchak'),
  (value: 'heart', label: 'Yurak'),
  (value: 'oblong', label: 'Cho‘ziq'),
  (value: 'diamond', label: 'Olmos'),
];

const List<ProfileOption> kOccasionOptions = [
  (value: '', label: 'Tanlanmagan'),
  (value: 'office', label: 'Ish / ofis'),
  (value: 'study', label: 'O‘qish'),
  (value: 'event', label: 'Tadbir'),
  (value: 'casual_street', label: 'Kundalik'),
  (value: 'sport', label: 'Sport'),
  (value: 'travel', label: 'Sayohat'),
  (value: 'home', label: 'Uy'),
  (value: 'other', label: 'Boshqa'),
];

const List<ProfileOption> kSeasonOptions = [
  (value: '', label: 'Tanlanmagan'),
  (value: 'spring', label: 'Bahor'),
  (value: 'summer', label: 'Yoz'),
  (value: 'autumn', label: 'Kuz'),
  (value: 'winter', label: 'Qish'),
];

const List<ProfileOption> kStyleIntentOptions = [
  (value: '', label: 'Tanlanmagan'),
  (value: 'minimal', label: 'Minimal'),
  (value: 'classic', label: 'Klassik'),
  (value: 'street', label: 'Street'),
  (value: 'elegant', label: 'Elegant'),
  (value: 'trendy', label: 'Trendy'),
  (value: 'sport_chic', label: 'Sport-chic'),
];

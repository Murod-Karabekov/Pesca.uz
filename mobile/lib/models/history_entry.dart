class HistoryEntry {
  const HistoryEntry({
    required this.id,
    required this.createdAtIso,
    required this.profile,
    required this.recommendations,
    this.photoFilename,
  });

  final int id;
  final String createdAtIso;
  final Map<String, dynamic> profile;
  final List<Map<String, dynamic>> recommendations;
  final String? photoFilename;

  factory HistoryEntry.fromJson(Map<String, dynamic> j) {
    final recs = j['recommendations'] as List<dynamic>? ?? [];
    return HistoryEntry(
      id: j['id'] as int,
      createdAtIso: j['createdAt'] as String,
      profile: Map<String, dynamic>.from(j['profile'] as Map<dynamic, dynamic>),
      recommendations: recs.map((e) => Map<String, dynamic>.from(e as Map)).toList(),
      photoFilename: j['photoFilename'] as String?,
    );
  }
}

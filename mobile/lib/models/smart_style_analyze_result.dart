class ProductRecommendation {
  const ProductRecommendation({
    required this.id,
    required this.name,
    required this.price,
    required this.currency,
    this.imageUrl,
    required this.score,
    required this.scoreLabel,
  });

  final int id;
  final String name;
  final String price;
  final String currency;
  final String? imageUrl;
  final int score;
  final String scoreLabel;

  factory ProductRecommendation.fromJson(Map<String, dynamic> j) {
    return ProductRecommendation(
      id: j['id'] as int,
      name: j['name'] as String,
      price: j['price'] as String,
      currency: j['currency'] as String? ?? 'UZS',
      imageUrl: j['imageUrl'] as String?,
      score: j['score'] as int,
      scoreLabel: j['scoreLabel'] as String? ?? '',
    );
  }
}

class SmartStyleAnalyzeResult {
  const SmartStyleAnalyzeResult({
    required this.usedAssumedProfile,
    required this.savedToAccount,
    this.message,
    required this.tips,
    required this.recommendations,
  });

  final bool usedAssumedProfile;
  final bool savedToAccount;
  final String? message;
  final List<String> tips;
  final List<ProductRecommendation> recommendations;

  factory SmartStyleAnalyzeResult.fromJson(Map<String, dynamic> j) {
    final rawTips = j['tips'] as List<dynamic>? ?? [];
    final rawRecs = j['recommendations'] as List<dynamic>? ?? [];

    return SmartStyleAnalyzeResult(
      usedAssumedProfile: j['usedAssumedProfile'] as bool? ?? false,
      savedToAccount: j['savedToAccount'] as bool? ?? false,
      message: j['message'] as String?,
      tips: rawTips.map((e) => e.toString()).toList(),
      recommendations: rawRecs
          .map((e) => ProductRecommendation.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

import 'package:flutter_test/flutter_test.dart';
import 'package:pesca_smartstyle/app.dart';

void main() {
  testWidgets('SmartStyle bosh sahifa chiqadi', (WidgetTester tester) async {
    await tester.pumpWidget(const PescaSmartStyleApp());
    await tester.pumpAndSettle();

    expect(find.text('SmartStyle'), findsOneWidget);
  });
}

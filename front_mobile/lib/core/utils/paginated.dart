/// Shared pagination wrapper for every list endpoint (Batch 3 §1: {"data":
/// [...], "meta": {"page","per_page","total"}}). Used by every feature's
/// repository so list Cubits share one shape (instructions §9).
class Paginated<T> {
  const Paginated({required this.items, required this.page, required this.perPage, required this.total});

  final List<T> items;
  final int page;
  final int perPage;
  final int total;

  int get totalPages => total == 0 ? 1 : (total / perPage).ceil();
  bool get hasNextPage => page < totalPages;
  bool get hasPreviousPage => page > 1;
}

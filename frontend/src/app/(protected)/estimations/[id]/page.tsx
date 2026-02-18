import EstimationEditorClient from "./EstimationEditorClient";

export async function generateStaticParams() {
  return [{ id: "0" }];
}

export default function EstimationEditorPage() {
  return <EstimationEditorClient />;
}

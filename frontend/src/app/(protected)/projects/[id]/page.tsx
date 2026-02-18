import ProjectDetailClient from "./ProjectDetailClient";

export async function generateStaticParams() {
  return [{ id: "0" }];
}

export default function ProjectDetailPage() {
  return <ProjectDetailClient />;
}

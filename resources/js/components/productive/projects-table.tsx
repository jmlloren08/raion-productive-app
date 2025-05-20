import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../ui/table";
import { type Company, type Project } from "@/types/productive";

interface ProjectsTableProps {
  projects: Project[];
  companies: Record<string, Company>;
  searchQuery?: string;
  page?: number;
  perPage?: number;
}

export function ProjectsTable({ projects, companies }: ProjectsTableProps) {
  const formatDate = (date: string | undefined) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString();
  };

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Company</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Created At</TableHead>
            <TableHead>Updated At</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {projects.map((project) => (
            <TableRow key={project.id}>
              <TableCell className="font-medium">{project.name}</TableCell>
              <TableCell>
                {companies[project.companyId]?.name || "No Company"}
              </TableCell>
              <TableCell>{project.status === 1 ? "Active" : "Archived"}</TableCell>
              <TableCell>{formatDate(project.createdAt)}</TableCell>
              <TableCell>{formatDate(project.updatedAt)}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}

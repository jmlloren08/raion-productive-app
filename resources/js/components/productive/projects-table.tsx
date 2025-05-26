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

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Company</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Created At</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {projects.map((project) => (
            <TableRow key={project.id}>
              <TableCell className="font-medium">{project.name}</TableCell>
              <TableCell>
                {companies[project.company_id]?.name || "No Company"}
              </TableCell>
              <TableCell>{project.archived_at ? "Archived" : "Active"}</TableCell>
              <TableCell>{project.project_type_id === 1 ? "Internal" : "Client"}</TableCell>
              <TableCell>{project.created_at_api && new Date(project.created_at_api).toLocaleString()}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
